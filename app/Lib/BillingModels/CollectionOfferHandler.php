<?php

namespace App\Lib\BillingModels;

use App\Facades\SMC;
use App\Jobs\CollectionOfferSubscriptionsUpdate;
use App\Lib\Orders\VolumeDiscountOrderHandler;
use App\Models\Offer\CollectionOfferProduct;
use App\Models\Offer\CollectionSubscription;
use App\Models\Offer\Type;
use App\Models\Order;
use App\Models\Order\Subscription;
use App\Models\OrderAttributes\Backorder;
use App\Models\OrderProductBundle;
use App\Models\ProductInventory;
use App\Models\SubscriptionHoldType;
use fileLogger;
use Illuminate\Support\Facades\Queue;
use products\add_on;
use system_module_control;
use Carbon\Carbon;

class CollectionOfferHandler
{
    // All the order confirmation emails for collection offer
    public const CONFIRMATION_EMAIL_DELAY_MINUTES = 1;

    /**
     * This function is for Collection Offer Orders Only
     *
     * This method is used right after the order got created
     * Here we would:
     *  - Create a new collection subscription if it's initial (otherwise use existed linked to the order.
     *  - Take purchased products and mark them as purchased by linking them to collection subscription
     *  - Mark as skipped all the products prior to starting position, defined by consumer through initial order request
     *
     * Additionally, we would proceed to next function for scheduling next recurring product without checking the inventory,
     * and we would queue that function for initial orders, so it won't slow down the new order request
     *
     * @param int $orderId
     * @param array $startingProducts
     */
    public static function handleCollectionOfferForCreatedOrderRecord(int $orderId, array $startingProducts = []): void
    {
        try {
            if (system_module_control::check(SMC::COLLECTIONS_OFFER) && $mainOrder = Order::find($orderId)) {
                $subscriptionsToCreate = [];
                $existedSubscriptions  = collect();
                $childrenSubscriptions = collect();
                $isInitialOrder        = $mainOrder->is_initial_order;

                // IF: We already have order item record created for this order, it means we ran this function already
                // THEN: Just skip this function so we don't run it more than once on a single order record
                if ($mainOrder->orderItems()->count()) {
                    fileLogger::log_flow(__METHOD__ . " - Collection Offer Record has already being created for this order. Order {$orderId}");

                    return;
                }

                // IF: this is re-bill order
                // THEN: find all existed collection subscriptions
                // AND: these that needs to be created
                // IF: initial order
                // THEN: just these that needs to be created
                foreach ($mainOrder->all_order_items as $order) {
                    if (
                        ($orderProduct = $order->order_product)
                        && ($orderSubscription = $order->order_subscription)
                        && $orderSubscription->billing_model_id // ignore PAIR PRODUCT
                        && $orderSubscription->bill_by_type_id // ignore STRAIGHT SALE
                        && $orderSubscription->offer
                        && $orderSubscription->offer->isCollectionType()
                    ) {
                        $subscriptionsToCreate[$orderSubscription->offer_id][$orderSubscription->billing_model_id][] = $orderProduct->product_id;
                    }
                }

                // IF: this order does not have collection subscriptions
                // THEN: Ignore anything else in this function
                if (! $subscriptionsToCreate) {
                    fileLogger::log_flow(__METHOD__ . " - Non collection offer order. Order {$orderId}");

                    return;
                }

                // IF: this is re-bill order
                // THEN: Find existed subscriptions from the parent order
                if (! $isInitialOrder) {
                    $existedSubscriptions  = $mainOrder->parent->subscriptions;
                    $childrenSubscriptions = $mainOrder->parent->childrenSubscriptions();
                }

                foreach ($subscriptionsToCreate as $offerId => $billingModelIds) {
                    foreach ($billingModelIds as $billingModelId => $productIds) {
                        // IF: this is re-bill
                        // AND: subscription already exist for this `Offer ID` and `Billing Model ID` combination based on the parent order
                        // THEN: search if subscription already exist for this offer and BM IDs combination
                        $subscription = $existedSubscriptions->where('offer_id', $offerId)->where('billing_model_id', $billingModelId)->first();

                        // ELSE IF: we didn't find existed subscription for it
                        // AND: we have found children subscriptions
                        // THEN: search if child subscription already exist for this offer and BM IDs combination
                        if (! $subscription && count($childrenSubscriptions)) {
                            $subscription = $childrenSubscriptions->where('offer_id', $offerId)->where('billing_model_id', $billingModelId)->first();
                        }

                        // IF: subscription does not exists
                        // THEN: Create new Subscription record
                        if (! $subscriptionId = $subscription->id ?? null) {
                            $subscriptionId = Subscription::create([
                                'contact_id'       => $mainOrder->contact->id,
                                'offer_id'         => $offerId,
                                'billing_model_id' => $billingModelId,
                                'offer_type_id'    => Type::TYPE_COLLECTION,
                            ])->id;

                            // Create Collection subscription record
                            CollectionSubscription::create(['subscription_id' => $subscriptionId]);

                            fileLogger::log_flow(__METHOD__ . " - New collection subscription ID created: {$subscriptionId}. Order {$orderId}");

                            $mainOrder->addHistoryNote(
                                'history-note-new-collection-subscription',
                                "New collection subscription was started for Offer: {$offerId}, Billing Model: {$billingModelId}. Purchased Product Ids: " . implode(',', $productIds)
                            );
                        } else {
                            $message = "Product IDs: " . implode(',', $productIds) . " were purchased through";
                            $mainOrder->addHistoryNote(
                                'history-note-collection-subscription-purchased-products',
                                "{$message} Offer: {$offerId}, Billing Model: {$billingModelId}"
                            );
                            fileLogger::log_flow(__METHOD__ . " - {$message} subscription ID: {$subscriptionId}. Order {$orderId}");
                        }

                        foreach ($productIds as $productId) {
                            // Create Order Item for each product with subscription attached to it
                            $orderItem = $mainOrder->orderItems()->firstOrCreate([
                                'product_id'      => $productId,
                                'subscription_id' => $subscriptionId,
                            ]);

                            // Save this product as purchased for current subscription
                            $orderItem->subscription->offerSubscription
                                ->purchasedProducts()
                                ->firstOrCreate(['product_id' => $productId]);
                        }

                        if ($subscription->is_child) {
                            $mainOrder->scheduleAnnouncement();
                            $mainOrder->addHistoryNote(
                                'history-note-announcement-scheduled',
                                "Announcement has been scheduled for Offer: {$subscription->offer_id} and Billing Model: {$subscription->billing_model_id}"
                            );
                        }

                        // IF: order is initial
                        // AND: we have received starting issue for this subscription in the order create request body
                        // THEN: get all the positions before that product ID and mark them as skipped
                        if ($isInitialOrder && $startingProducts && isset($startingProducts[$offerId][$billingModelId])) {
                            $offerSubscription = CollectionSubscription::where('subscription_id', $subscriptionId)->first();
                            $productId         = (int) $startingProducts[$offerId][$billingModelId];
                            $collectionOffer   = $offerSubscription->subscription->offer->offer_details;
                            $positions         = $collectionOffer->products;
                            $skippedProductIds = [];

                            foreach ($positions as $position) {
                                // IF: we have reached to product that we are starting from
                                // THEN: exit this loop
                                if ($position->product_id === $productId) {
                                    break;
                                }

                                // Store this product as skipped for current subscription.
                                // Do not update if product is already there (actually purchased)
                                $offerSubscription
                                    ->purchasedProducts()
                                    ->firstOrCreate(
                                        ['product_id' => $position->product_id],
                                        ['is_skipped' => 1]
                                    );

                                $skippedProductIds[] = $position->product_id;
                            }

                            $message = "Product IDs: " . implode(',', $skippedProductIds) . " were marked as skipped for";

                            if ($skippedProductIds) {
                                $mainOrder->addHistoryNote(
                                    'history-note-skipped-products',
                                    "{$message} Offer: {$offerId} and Billing Model: {$billingModelId}."
                                );
                            }

                            fileLogger::log_flow(__METHOD__ . " - {$message} subscription ID: {$subscriptionId}. Order {$orderId}");
                        }

                        // IF: This subscription does not have a child subscription yet
                        // AND: does not have parent subscription
                        if ($subscription = Subscription::doesntHave('child')->doesntHave('parent')->find($subscriptionId)) {
                            $offerLink = $subscription->offer->linkToChild;

                            // IF: This offer has child offer linked
                            // AND: Re-bill depth matches
                            // THEN: Create a new linked subscription (Negative Options)
                            if ($offerLink && $mainOrder->rebillDepth === $offerLink->rebill_depth) {
                                // Create New subscription record
                                $newSubscription = Subscription::create([
                                    'contact_id'       => $mainOrder->contact->id,
                                    'offer_id'         => $offerLink->linked_offer_id,
                                    'billing_model_id' => $offerLink->billing_model_id,
                                    'offer_type_id'    => Type::TYPE_COLLECTION,
                                ]);

                                // Create New Collection subscription record
                                CollectionSubscription::create(['subscription_id' => $newSubscription->id]);

                                fileLogger::log_flow(__METHOD__ . " - New child collection subscription ID created: {$newSubscription->id}");

                                $mainOrder->addHistoryNote(
                                    'history-note-new-collection-subscription',
                                    "New child collection subscription was scheduled for Offer: {$newSubscription->offer_id}, Billing Model: {$newSubscription->billing_model_id}."
                                );

                                // Link these 2 subscriptions together
                                $subscription->linkToChild()->updateOrCreate(['linked_subscription_id' => $newSubscription->id]);

                                // Getting initial first position out of this linked offer to start from
                                $initialPosition = $newSubscription->offer->offer_details->products()->first();

                                // Get next recurring date for this initial Negative option collection subscription
                                $nextRecurringDate = $nextRecurringDateOfChild = $offerLink->getNextEligibleRecurringDate();

                                //if difference is less than 7 days then update the recurring date of special order to match 7 days diff
                                $appendNotes          = '';
                                $parentRecurringOrder = $subscription->activeRecurringItems()->first();

                                if ($parentRecurringOrder && $parentRecurringOrder->next_valid_recurring_date) {
                                    $diffInDays    = $parentRecurringOrder->next_valid_recurring_date->diffInDays($nextRecurringDate);
                                    $minBilledDays = 7;

                                    if ($diffInDays < $minBilledDays) {
                                        $nextRecurringDate = Carbon::parse($nextRecurringDate);

                                        if ($nextRecurringDate->lessThanOrEqualTo($parentRecurringOrder->next_valid_recurring_date)) {
                                            $addDays = $minBilledDays + $diffInDays;
                                        } else {
                                            $addDays = $minBilledDays - $diffInDays;
                                        }
                                        $nextRecurringDate->addDays($addDays);
                                        $nextRecurringDate = $nextRecurringDate->format('Y-m-d');
                                        $appendNotes       = " updated from {$nextRecurringDateOfChild}";
                                    }
                                }

                                $nextProductId     = $initialPosition->product_id;
                                $nextQty           = $initialPosition->product_qty;
                                $nextPrice         = $initialPosition->product_unit_price;

                                // Schedule new subscription out of this initial product
                                $addon = new add_on(
                                    $orderId,
                                    $nextProductId,
                                    $nextQty,
                                    [],
                                    $nextRecurringDate,
                                    true,
                                    $nextPrice,
                                    false,
                                    0,
                                    Order::TYPE_ORDER,
                                    true
                                );

                                // Set the correct offer and BM IDs for this subscription + next recurring price override
                                if ($subOrder = $addon->getAddonSubOrder()) {
                                    $subOrder->update_offer($newSubscription->offer_id);
                                    $subOrder->update_billing_model($newSubscription->billing_model_id, false);
                                }

                                $mainOrder->addHistoryNote(
                                    'history-note-subscription-add-product',
                                    "Product {$nextProductId} for child subscription was scheduled to recur on {$nextRecurringDate}{$appendNotes}"
                                );

                                $mainOrder->scheduleAnnouncement();
                                $mainOrder->addHistoryNote(
                                    'history-note-announcement-scheduled',
                                    "Announcement has been scheduled for child subscription"
                                );

                                fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. Product ID: {$nextProductId}. Child subscription addon has been scheduled for a new linked subscription {$newSubscription->id}");
                            }
                        }
                    }
                }

                // IF: initial order
                // THEN: queue the collection subscription scheduling part so we don't  overload the new order request
                // IF: re-bill
                // THEN: do collection subscription scheduling part right away
                if ($isInitialOrder) {
                    self::queueUpdateCollectionOfferSubscriptions($orderId);
                } else {
                    self::updateCollectionOfferSubscriptions($orderId);
                }
            }
        } catch (\Throwable $e) {
            fileLogger::log_error($e->getMessage(), __METHOD__ . " - Collection Offer Handler Exception. Order {$orderId}");
        }
    }

    /**
     * This function is for Collection Offer Orders Only
     *
     * Here we would queue scheduling next products for collection subscription
     * for initial orders, so the new order request proceed faster
     *
     * @param int $orderId
     */
    protected static function queueUpdateCollectionOfferSubscriptions(int $orderId): void
    {
        Queue::pushOn(
            CollectionOfferSubscriptionsUpdate::getQueueName(),
            new CollectionOfferSubscriptionsUpdate(
                CRM_AUTH_KEY,
                $orderId
            )
        );

        fileLogger::log_flow(__METHOD__ . " - Initial Order ID: {$orderId} has been queued to update next recurring products");
    }

    /**
     * This function is for Collection Offer Orders Only
     *
     * We use it to update next recurring products which includes:
     *  - update existed subscriptions to next recurring product
     *  - add addons to the order based on collection offer setup
     *  - stop remaining subscriptions that have not being used
     *
     * Also when inventory is requested we would make those updates based on availability which we do on re-bill only
     * Additionally we would backorder the entire order when we don't have anything in stock
     *
     * @param int $orderId
     * @param bool $checkInventory
     * @param int|null $rechargeOrderId
     */
    public static function updateCollectionOfferSubscriptions(int $orderId, bool $checkInventory = false, ?int $rechargeOrderId = null): void
    {
        try {
            if (system_module_control::check(SMC::COLLECTIONS_OFFER) && $mainOrder = Order::find($orderId)) {
                $collectionSubscriptions = [];
                $addOnOrderSubscriptions = [];
                $recurringDate           = null;
                $activeRecurringItems    = $mainOrder->active_recurring_items;

                // IF: if order is backorder with fulfillment type
                // THEN: ignore next recurring logic as we should not be processing this order
                // until it's fulfilled
                if ((string) $mainOrder->backorderType === Backorder::FULFILLMENT) {
                    fileLogger::log_flow(__METHOD__ . " - The order is still on backorder due to fulfillment. Therefore we cannot proceed with re-bill. Order ID: {$orderId}");

                    return;
                }

                // IF: inventory is requested
                // THEN: we are about to re-bill this order
                //      @method get_bundled_upsells() - we have 3 versions of this method that we are replicating here
                // SO: we want to follow the same process we use to determine which products should be re-billed
                // THEREFORE: we would only update and check those specific subscriptions
                // AND: put on backorder by checking inventory for potentially products to be re-billed
                if ($checkInventory) {
                    // IF: recharge order was passed
                    // AND: the ID is the same as main order
                    if ($rechargeOrderId === $orderId) {
                        // THEN: it's force bill of a main order OR main product
                        // Filter out any other products that don't have the same recurring date as main
                        $activeRecurringItems = $activeRecurringItems->filter(static function ($item) use ($mainOrder) {
                            return $mainOrder->next_valid_recurring_date->eq($item->next_valid_recurring_date);
                        });
                    } else if ($rechargeOrderId) {
                        // IF: this ID is an upsell
                        // THEN: Keep requested upsell order id AND skip main product
                        // ALSO: keep those upsells that are scheduled prior today OR today
                        $activeRecurringItems = $activeRecurringItems->filter(static function ($item) use ($rechargeOrderId, $orderId) {
                            return $rechargeOrderId === $item->id || ($orderId !== $item->id && $item->next_valid_recurring_date->lte(now()));
                        });
                    } else {
                        // IF: $rechargeOrderId is NULL
                        // THEN: Keep requested main order
                        // AND: keep those upsells that are scheduled prior today OR today
                        $activeRecurringItems = $activeRecurringItems->filter(static function ($item) use ($orderId) {
                            return $orderId === $item->id || $item->next_valid_recurring_date->lte(now());
                        });
                    }
                }

                foreach ($activeRecurringItems as $order) {
                    $orderSubscription = $order->order_subscription;
                    $orderProduct      = $order->order_product;

                    if ($orderSubscription && $orderSubscription->offer && $orderSubscription->offer->isCollectionType()) {
                        // IF: order item exists
                        // THEN: we have collection subscription attached to it
                        // IF: doesn't exist
                        // THEN: it's most likely an add-on that is created for the next order
                        // AND: don't have order item created yet, so will try to find matching subscription
                        if ($orderProduct->orderItem) {
                            $collectionSubscriptions[$orderProduct->orderItem->subscription_id][] = $orderSubscription;
                        } elseif ($order->isUpsell() && $order->is_add_on) {
                            $addOnOrderSubscriptions[] = $orderSubscription;
                        }
                    }
                }

                // IF: we found add-ons
                // THEN: try to find related active or inactive subscription for it based on this order
                if ($addOnOrderSubscriptions) {
                    $existedSubscriptions = $mainOrder->subscriptions;

                    // Find all the children subscriptions related to this order
                    // This is needed in that case when we just have scheduled child subscription
                    // but because we have not actually started this subscription yet, we won't have order item record for it
                    $childrenSubscriptions = $mainOrder->childrenSubscriptions();

                    foreach ($addOnOrderSubscriptions as $orderSubscription) {
                        $subscription = $existedSubscriptions
                            ->where('offer_id', $orderSubscription->offer_id)
                            ->where('billing_model_id', $orderSubscription->billing_model_id)
                            ->first();

                        // IF: we didn't match this addon to existed subscription
                        // THEN: maybe it was just scheduled and it's a child subscription
                        if (! $subscription && count($childrenSubscriptions)) {
                            $subscription = $childrenSubscriptions
                                ->where('offer_id', $orderSubscription->offer_id)
                                ->where('billing_model_id', $orderSubscription->billing_model_id)
                                ->first();
                        }

                        if ($subscription) {
                            $collectionSubscriptions[$subscription->id][] = $orderSubscription;
                        }
                    }
                }

                // IF: this order does not have active collection subscriptions
                // THEN: Stop here and don't do anything else
                if (! $collectionSubscriptions) {
                    fileLogger::log_flow(__METHOD__ . " - Non collection offer order");

                    return;
                }

                // IF: inventory is requested
                // THEN: check if SMC is ON
                $shouldCheckInventory = $checkInventory && system_module_control::check(SMC::INVENTORY_AWARENESS);

                foreach ($collectionSubscriptions as $subscriptionId => $orderSubscriptions) {
                    if (($subscription = Subscription::find($subscriptionId)) && $subscription->offer && $collectionSubscription = $subscription->offerSubscription) {
                        if ($subscription->is_completed) {
                            fileLogger::log_flow(__METHOD__ . " - Subscription has already been completed. Skipping...");
                            continue;
                        }

                        if (! $subscription->is_active) {
                            fileLogger::log_flow(__METHOD__ . " - Subscription was canceled or paused. Skipping...");
                            continue;
                        }

                        $collectionOffer     = $subscription->offer->offer_details;
                        $remainedRequiredQty = $collectionOffer->qty_per_purchase;
                        $purchasedProductIds = $collectionSubscription->purchasedProducts()->pluck('product_id')->all();
                        $availablePositions  = $collectionOffer->products()->whereNotIn('product_id', $purchasedProductIds)->get();
                        // Resetting these values for every subscription
                        $shouldBackorder   = false;
                        $isSkippedPosition = false;
                        $nextPositions     = [];
                        $nextRecurringDate = null;
                        $existedSubOrderId = null;
                        $existedSubTypeId  = null;

                        foreach ($availablePositions as $position) {
                            // IF: we have reached required quantity
                            // THEN: Stop searching for available positions
                            if (! $remainedRequiredQty) {
                                break;
                            }

                            if ($shouldCheckInventory) {
                                $warehouseId = $mainOrder->campaign->warehouse_id ?? 0;

                                // IF: inventory record does not exist
                                // OR: we are requesting more than inventory availability is
                                // THEN: proceed with collection offer configuration
                                if (! self::checkProductInventory($orderId, $position, $warehouseId)) {
                                    // IF: at least one position from the next shipment is locked
                                    // AND: this position is unavailable
                                    // THEN: Backorder the entire subscription, don't go to next position
                                    if ($position->is_locked) {
                                        $shouldBackorder = true;
                                        fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}, Product ID: {$position->product_id}. Will set to backorder due to Inventory unavailable and position being locked");

                                        break;
                                    }

                                    // IF: it's not locked
                                    // THEN: just skip this position for now
                                    $isSkippedPosition = true;
                                    fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}, Product ID: {$position->product_id}. Skipping position as of right now");

                                    continue;
                                }
                            }

                            $nextPositions[] = $position;

                            $remainedRequiredQty--;
                        }

                        // IF: we don't have any available positions because we skipped unavailable
                        // THEN: backorder all of them and try again later
                        // OTHERWISE: stop recurring of all subscriptions since it got completed
                        if (! $shouldBackorder && ! $nextPositions && $isSkippedPosition) {
                            $shouldBackorder = true;
                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. All positions were skipped, set to backorder");
                        }

                        if ($shouldBackorder) {
                            if (! $mainOrder->backorderType) {
                                Backorder::createForOrder($orderId, Backorder::RE_BILL);
                                $mainOrder->addHistoryNote(
                                    'history-note-backorder',
                                    "Order was set to backorder due to one or more positions being out of stock."
                                );
                            }

                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId} was set to backorder");

                            // We should still update next recurring products of this order ignoring the inventory check
                            // Otherwise we won't get position updates until it gets back in stock
                            self::updateCollectionOfferSubscriptions($orderId);

                            break;
                        }

                        if ($checkInventory && $mainOrder->backorderType && $mainOrder->backorderType->delete()) {
                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. Backorder status was removed");
                            $mainOrder->addHistoryNote(
                                'history-note-backorder-removed',
                                "Backorder status was removed from the order."
                            );
                        }

                        // We should always start from the main order
                        $orderSubscriptions = collect($orderSubscriptions)->sortBy('type_id');
                        $nextProductsIds    = array_column($nextPositions, 'product_id');

                        // If any of the next products are bundles, remove their scheduled children if there are any
                        if (! empty($nextProductsIds)) {
                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. Removing all the children for the following next product IDs: " . implode(', ', $nextProductsIds));

                            OrderProductBundle::where([
                                'order_id'      => $orderId,
                                'is_next_cycle' => 1,
                            ])
                                ->whereIn('bundle_id', $nextProductsIds)
                                ->delete();
                        }

                        foreach ($nextPositions as $nextPosition) {
                            $nextProductId = $nextPosition->product_id;
                            $nextPrice     = $nextPosition->product_unit_price;
                            $nextQty       = $nextPosition->product_qty;
                            // We don't support variants yet
                            $nextVariantId = 0;

                            foreach ($orderSubscriptions as $key => $orderSubscription) {
                                $order    = $orderSubscription->order;
                                $logPrefix = __METHOD__ . " - Order ID: {$orderId}. Subscription ID: {$order->subscription_id}. ";

                                // Because all these orders related to the same subscription,
                                // they will have the same recurring date, BM ID and offer fetch from the matched subscription
                                $nextRecurringDate = $nextRecurringDate ?? $order->next_valid_recurring_date;
                                $existedSubOrderId = $existedSubOrderId ?? $order->id;
                                $existedSubTypeId  = $existedSubTypeId ?? $order->type_id;
                                $isMain            = $order->isMain();

                                // If current schedule product is a bundle and have not been deleted yet, delete existed records
                                if (
                                    ! \in_array($orderSubscription->next_recurring_product, $nextProductsIds, true)
                                    && $orderSubscription->next_recurring_product()->first()->is_bundle
                                ) {
                                    fileLogger::log_flow($logPrefix . "Current product ID {$orderSubscription->next_recurring_product} is a bundle, removing all children");

                                    OrderProductBundle::where([
                                        'order_id'      => $orderId,
                                        'bundle_id'     => $orderSubscription->next_recurring_product,
                                        'main_flag'      => $isMain,
                                        'is_next_cycle' => 1,
                                    ])->delete();
                                }

                                $orderSubscription->next_recurring_product           = $nextProductId;
                                $orderSubscription->next_recurring_price             = $nextPrice;
                                $orderSubscription->next_recurring_quantity          = $nextQty;
                                $orderSubscription->next_recurring_variant           = $nextVariantId;
                                $orderSubscription->is_preserve_price                = 0;
                                $orderSubscription->is_next_recurring_price_override = 1;

                                // If new next recurring product is pre-built bundle. Create required records.
                                if (
                                    $nextPosition->product->is_bundle &&
                                    $nextPosition->product->is_prebuilt_bundle &&
                                    $children = $nextPosition->product->bundle_children
                                ) {
                                    fileLogger::log_flow($logPrefix . "New product ID {$nextProductId} is a custom bundle, creating children");

                                    foreach ($children as $child) {
                                        OrderProductBundle::create([
                                            'order_id'      => $orderId,
                                            'bundle_id'     => $orderSubscription->next_recurring_product,
                                            'main_flag'      => $isMain,
                                            'product_id'    => $child->product_id,
                                            'quantity'      => $child->quantity,
                                            'is_next_cycle' => 1,
                                        ]);
                                    }
                                }

                                // IF: the subscription has values changed
                                // THEN: update subscription
                                if ($orderSubscription->isDirty()) {
                                    $orderSubscription->save();

                                    fileLogger::log_flow($logPrefix . "Next recurring information was updated to product {$nextProductId}-{$nextVariantId}, {$nextPrice}, {$nextQty}");

                                    // IF: this order is add on
                                    // THEN: update add on fields as well to the new values
                                    if ($order->isUpsell() && $order->is_add_on) {
                                        fileLogger::log_flow($logPrefix . "Trying to update addon information");

                                        $order->order_product()->update([
                                            'products_id'       => $nextProductId,
                                            'products_price'    => 0.0, // Because it's just scheduled the addon current order product price should be 0.00
                                            'products_quantity' => $nextQty,
                                            'variant_id'        => $nextVariantId,
                                        ]);

                                        fileLogger::log_flow($logPrefix . "Addon information was updated");
                                    }

                                    $mainOrder->addHistoryNote(
                                        'recurring-product-updated',
                                        "{$order->subscription_id}:{$nextProductId}"
                                    );
                                }

                                // Because we have matched current position to this subscription
                                // Remove these subscriptions from the list, so we don't overuse it again
                                // And go to the next position
                                unset($orderSubscriptions[$key]);
                                continue 2;
                            }

                            // IF: in some odd case we couldn't fetch these values
                            // THEN: overwrite them with main order stuff
                            // BUT: this should never be the case
                            $nextRecurringDate = $nextRecurringDate ?? $mainOrder->next_valid_recurring_date;
                            $existedSubOrderId = $existedSubOrderId ?? $mainOrder->id;
                            $existedSubTypeId  = $existedSubTypeId ?? $mainOrder->type_id;

                            // IF: we don't have any active available subscriptions to use for current position
                            // THEN: Create an ADD-ON based on current subscription's set up
                            $addon = new add_on(
                                $existedSubOrderId,
                                $nextProductId,
                                $nextQty,
                                [],
                                $nextRecurringDate ? $nextRecurringDate->format('Y-m-d') : null,
                                true,
                                $nextPrice,
                                false,
                                $nextVariantId,
                                $existedSubTypeId,
                                true
                            );

                            /** Set the correct BM IDs for this subscription and load BM discount */
                            if ($subOrder = $addon->getAddonSubOrder()) {
                                $subOrder->update_billing_model($subscription->billing_model_id, false);
                            }

                            $mainOrder->addHistoryNote(
                                'history-note-subscription-add-product',
                                "({$nextQty}) Additional Product {$nextProductId} added to recur on Offer: {$subscription->offer_id}, Billing Model: {$subscription->billing_model_id}."
                            );

                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. Product ID: {$nextProductId}. Addon has been added for subscription {$subscription->id}");
                        }

                        // BECAUSE: when we stop main we do swap, it messes around with subscription objects
                        // to prevent issues with that we want to sort subscriptions so first we cancel upsells
                        // so by the time we cancel main, we won't use these upsells
                        $orderSubscriptions = collect($orderSubscriptions)->sortByDesc('type_id');

                        // IF: we don't have any available positions, mark subscription as completed
                        $isSubCompleted = ! $nextPositions;

                        if ($isSubCompleted) {
                            $subscription->update(['is_completed' => true, 'is_active' => false]);
                            $mainOrder->addHistoryNote('history-note-all-collection-orders-placed', "Collection subscription was completed for Offer: {$subscription->offer_id} and Billing Model: {$subscription->billing_model_id}.");
                        }

                        // IF: we have any active subscriptions left that we didn't use
                        // OR: we have completed this collection subscription already
                        // THEN: just STOP these subscriptions
                        foreach ($orderSubscriptions as $orderSubscription) {
                            // We want to have a special Complete hold type, so we can properly resume subscription
                            $orderSubscription->order->stopRecurring($isSubCompleted ? SubscriptionHoldType::COMPLETE : SubscriptionHoldType::OFFER);
                            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}. Subscription ID: {$orderSubscription->order->subscription_id}. Recurring was stopped");
                        }
                    }
                }

                if (system_module_control::check(SMC::VOLUME_DISCOUNTS)) {
                    (new VolumeDiscountOrderHandler($orderId))->reCalculateVolumeDiscount();
                }

                RebuildForecastedRevenue("o.orders_id = {$orderId}");
            }
        } catch (\Throwable $e) {
            fileLogger::log_error($e->getMessage(), __METHOD__ . " - Collection Offer Handler Exception");
        }
    }

    /**
     * @param int $orderId
     * @param \App\Models\Offer\CollectionOfferProduct $position
     * @param int $warehouseId
     * @return bool
     */
    protected static function checkProductInventory(int $orderId, CollectionOfferProduct $position, int $warehouseId): bool
    {
        fileLogger::log_flow(__METHOD__ . " - Checking inventory for Order ID: {$orderId}, Product ID: {$position->product_id}, Warehouse ID: {$warehouseId}");

        // IF: we are fulfilling children we should check their inventory availability
        if ($position->product && $position->product->isBundleUsesChildrenSkus() && $position->product->children->count()) {
            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}, Product ID: {$position->product_id}. Checking inventory of a bundle product");

            return self::checkBundleProductInventory($orderId, $position, $warehouseId);
        }

        $inventory = ProductInventory::get($position->product_id, 0, $warehouseId);

        if (! $inventory || $inventory->getRemainingQuantity($position->product_qty, false) < 0) {
            fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}, Product ID: {$position->product_id}, Qty: {$position->product_qty}. Inventory unavailable");

            return false;
        }

        return true;
    }

    /**
     * @param int $orderId
     * @param \App\Models\Offer\CollectionOfferProduct $position
     * @param int $warehouseId
     * @return bool
     */
    protected static function checkBundleProductInventory(int $orderId, CollectionOfferProduct $position, int $warehouseId): bool
    {
        foreach ($position->product->children as $child) {
            $inventory = ProductInventory::get($child->product_id, 0, $warehouseId);
            // Currently our system does not take to consideration the bundle product qty,
            // we will keep the same logic for now, and later will use $position->product_qty here
            $productQty = 1;
            $childQty   = $productQty * $child->quantity;

            if (! $inventory || $inventory->getRemainingQuantity($childQty, false) < 0) {
                fileLogger::log_flow(__METHOD__ . " - Order ID: {$orderId}, Bundle Product ID: {$position->product_id}, Child Product ID: {$child->product_id}, Qty: {$childQty}. Inventory unavailable");

                return false;
            }
        }

        return true;
    }
}
