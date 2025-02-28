<?php


namespace App\Lib\Orders\SubscriptionOrders;

use App\Exceptions\Subscriptions\InvalidLineItemsException;
use App\Facades\SMC;
use App\Jobs\HighPriorityJobs\GracePeriodCreationJob;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateBillingModelRequest;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateNextRecurringDateRequest;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateNextRecurringPriceRequest;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateNextRecurringProductRequest;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateNextRecurringQuantityRequest;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Lib\Orders\SubscriptionOrders\ModuleRequests\UpdateRecurringPricePreservationRequest;
use App\Lib\Orders\VolumeDiscountOrderHandler;
use App\Models\BillingModel\OrderSubscription;
use App\Models\Order;
use App\Models\OrderAttributes\GracePeriodCounter;
use App\Models\OrderHistoryNote;
use App\Models\OrderProduct;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Readers\OrderReader;
use App\Models\Readers\UpsellReader;
use App\Models\Subscription;
use App\Models\Upsell;
use App\Models\UpsellProduct;
use App\Models\User;
use App\Services\Decline\HandleDecline;
use billing_models\api\subscription_order as bm_subscription;
use Illuminate\Support\Collection;
use App\Events\Order\RecurringDateUpdated;
use Illuminate\Support\Facades\Event;
use recurring_cron;
use system_module_control;
use Carbon\Carbon;

/**
 * Class SubscriptionOrderHandler
 * @package App\Lib\Orders\SubscriptionOrders
 */
class SubscriptionOrderHandler
{
    public const SOURCE_SMART_DUNNING    = \App\Services\SmartRetry\Client::class;
    public const SOURCE_GRACE_PERIOD_JOB = GracePeriodCreationJob::class;
    public const SOURCE_REBILL_CRON      = recurring_cron::class;
    public const SOURCE_HANDLE_DECLINE   = HandleDecline::class;

    /**
     * Update the next recurring date of 1 or more line items.
     * @param UpdateNextRecurringDateRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateNextRecurringDate(UpdateNextRecurringDateRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems = $this->deriveLineItems($request);
        $newDate   = $request->get('date');
        $response  = (new SubscriptionOrderUpdateResponse)
            ->setUpdateTypeRecurringDate()
            ->setNewValue($newDate);

        foreach ($lineItems as $order) {
            $order->updateRecurringDate($newDate);

            if ($order->isMain()) {
                $addOnOrders = $order->additional_products()
                    ->where('is_add_on', 1)->get();

                foreach ($addOnOrders as $addOnOrder) {
                    $addOnOrder->updateRecurringDate($newDate);
                }
            }

            // Dispatch event when recurring date gets updated.
            //
            Event::dispatch(new RecurringDateUpdated($order->id, $order->getOrderTypeId()));

            // Create an order history note for this line item.
            //
            $order->addHistoryNote(
                'history-note-changed-recurring-date',
                "Recurring date has been updated to {$newDate} for subscription {$order->subscription_id}"
            );
            $response->pushAffectedSubscriptionId($order->subscription_id);
            $this->reCalculateVolumeDiscount($order);
        }

        return $response;
    }


    /**
     * @param Order|OrderReader|Upsell|UpsellReader $order
     */
    protected function reCalculateVolumeDiscount($order): void
    {
        if (system_module_control::check(SMC::VOLUME_DISCOUNTS) && $order) {
            if ($order instanceof Upsell || $order instanceof UpsellReader) {
                $orderId = $order->getMainOrderIdAttribute();
            } else if ($order instanceof Order || $order instanceof OrderReader){
                $orderId = $order->orders_id;
            }

            if (isset($orderId)) {
                (new VolumeDiscountOrderHandler((int) $orderId))->reCalculateVolumeDiscount();
            }
        }
    }

    /**
     * Update the next recurring product of 1 or more line items.
     * @param UpdateNextRecurringProductRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateNextRecurringProduct(UpdateNextRecurringProductRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems           = $this->deriveLineItems($request);
        $newRecurringProduct = $request->get('next_recurring_product');
        $newRecurringVariant = $request->get('next_recurring_variant');
        $removeVariant       = (bool) $request->get('remove_variant', 0);
        $response            = new SubscriptionOrderUpdateResponse;
        $variantText         = '';
        $price               = null;

        if ($newRecurringVariant) {
            $variant             = ProductVariant::readOnly()->find($newRecurringVariant);
            $newRecurringProduct = $variant->product_id;
            $price               = $variant->price;
            $response->setUpdateTypeVariant()->setNewValue($newRecurringVariant);
            $variantText = " (variant #{$newRecurringVariant})";
        } else {
            if ($removeVariant) {
                $response->setUpdateTypeVariant()->setNewValue("0");
            }

            $response->setUpdateTypeProduct()->setNewValue($newRecurringProduct);

            if ($product = Product::readOnly()->find($newRecurringProduct)) {
                $price = $product->price;
            }
        }

        foreach ($lineItems as $order) {
            $order->updateNextRecurringProduct($newRecurringProduct, $newRecurringVariant, $removeVariant);

            $currencySymbol     = $order->currency->symbol_left;
            $nextRecurringPrice = $order->subscription_order->next_recurring_price;
            $priceMsg           = "";

            if (! is_null($nextRecurringPrice)) {
                $priceMsg = " and next recurring price was kept preserved to " . $currencySymbol . formatMoney($nextRecurringPrice) . '.';
            }

            if ($price !== null && empty($order->subscription_order->is_preserve_price)) {
                $order->updateNextRecurringPrice($price);
                $priceMsg = " and next recurring price was updated to product's price " . $currencySymbol . formatMoney($price) . '.';
            }

            $order->addHistoryNote(
                'history-note-changed-recurring-product',
                "Recurring product has been updated to product #{$newRecurringProduct}{$variantText} for subscription {$order->subscription_id}" . $priceMsg
            );
            $response->pushAffectedSubscriptionId($order->subscription_id);
            $this->reCalculateVolumeDiscount($order);

            //Re-calculate Forecasted Revenue
            RebuildForecastedRevenue("o.orders_id = {$order->order_id}");
        }

        return $response;
    }

    /**
     * Update the next recurring quantity of 1 or more line items.
     * @param UpdateNextRecurringQuantityRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateNextRecurringQuantity(UpdateNextRecurringQuantityRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems            = $this->deriveLineItems($request);
        $newRecurringQuantity = $request->get('next_recurring_quantity');
        $response             = (new SubscriptionOrderUpdateResponse)
            ->setUpdateTypeQuantity()
            ->setNewValue($newRecurringQuantity);

        foreach ($lineItems as $order) {
            $order->updateNextRecurringQuantity($newRecurringQuantity);
            $order->addHistoryNote(
                'history-note-changed-recurring-quantity',
                "Recurring quantity has been updated to {$newRecurringQuantity} for subscription {$order->subscription_id}"
            );
            $response->pushAffectedSubscriptionId($order->subscription_id);
            $this->reCalculateVolumeDiscount($order);

            //Re-calculate Forecasted Revenue
            RebuildForecastedRevenue("o.orders_id = {$order->order_id}");
        }

        return $response;
    }

    /**
     * Update the billing model of 1 or more line items.
     * @param UpdateBillingModelRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateBillingModel(UpdateBillingModelRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems      = $this->deriveLineItems($request);
        $billingModelId = $request->get('billing_model_id');
        $response       = (new SubscriptionOrderUpdateResponse)
            ->setUpdateTypeBillingModel()
            ->setNewValue($billingModelId);

        foreach ($lineItems as $order) {
            $order->updateRecurringBillingModel($billingModelId);
            $order->addHistoryNote(
                'history-note-changed-billing-model-id',
                "Billing model has been updated to #{$billingModelId} for subscription {$order->subscription_id}"
            );
            $response->pushAffectedSubscriptionId($order->subscription_id);
            $this->reCalculateVolumeDiscount($order);
        }

        return $response;
    }

    /**
     * Update the next recurring price and (optionally) the price preservation of 1 or more line items.
     * @param UpdateNextRecurringPriceRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateNextRecurringPrice(UpdateNextRecurringPriceRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems       = $this->deriveLineItems($request);
        $price           = $request->get('price');
        $isPreserve      = $request->get('is_preserve', 1);
        $formatted       = number_format($price, 2);
        $isPriceOverride = $request->get('is_next_recurring_price_override', 1);
        $preserveText    = '';
        $response        = (new SubscriptionOrderUpdateResponse)
            ->setUpdateTypePrice()
            ->setNewValue($price);

        if (!is_null($isPreserve)) {
            $preserveText = ', with preservation turned ' . ($isPreserve ? 'ON' : 'OFF');
        }

        foreach ($lineItems as $order) {
            $order->updateNextRecurringPrice($price, $isPreserve, $isPriceOverride);
            $order->addHistoryNote(
                'history-note-changed-recurring-price',
                "Next recurring price has been updated to {$formatted}{$preserveText} for subscription {$order->subscription_id}"
            );
            $response->pushAffectedSubscriptionId($order->subscription_id);
            $this->reCalculateVolumeDiscount($order);
            RebuildForecastedRevenue("o.orders_id = {$order->order_id}");
        }

        return $response;
    }

    /**
     * Update the next recurring price preservation of 1 or more line items.
     * @param UpdateRecurringPricePreservationRequest $request
     * @return SubscriptionOrderUpdateResponse
     * @throws InvalidLineItemsException
     */
    public function updateNextRecurringPricePreservation(UpdateRecurringPricePreservationRequest $request): SubscriptionOrderUpdateResponse
    {
        $lineItems    = $this->deriveLineItems($request);
        $isPreserve   = $request->get('is_preserve');
        $preserveText = $isPreserve ? 'ON' : 'OFF';
        $response     = (new SubscriptionOrderUpdateResponse)
            ->setUpdateTypePreservePrice()
            ->setNewValue($isPreserve);

        foreach ($lineItems as $order) {
            $order->updatePricePreservation((int) $isPreserve);
            $order->addHistoryNote(
                'history-note-changed-recurring-price-preservation',
                "Recurring price preservation has been updated to {$preserveText} for subscription {$order->subscription_id}"
            );
            $this->reCalculateVolumeDiscount($order);
            $response->pushAffectedSubscriptionId($order->subscription_id);
        }

        return $response;
    }

    /**
     * Fetch a collection of line items based upon the criteria set by the request.
     * @param ModuleRequest $request
     * @return Collection
     * @throws InvalidLineItemsException
     */
    protected function deriveLineItems(ModuleRequest $request): Collection
    {
        $collection     = null;
        $subscriptionId = $request->get('subscription_id');
        $orderId        = $request->get('order_id');

        if ($subscriptionId) {
            // We have the subscription ID, only fetch one line item.
            //
            if ($lineItem = (new Subscription)->getSubscriptionById($subscriptionId)) {
                $collection = collect([
                    $subscriptionId => $lineItem
                ]);
            }
        } else if ($orderId && ($order = Order::find($orderId))) {
            $lineItems     = [];
            $productId     = $request->get('product_id');
            $variantId     = $request->get('variant_id');
            $allOrderItems = $order->all_order_items;

            if ($productId || $variantId) {
                // We have an order ID and a product ID, fetch one or more line items with this criteria.
                //
                foreach ($allOrderItems as $item) {
                    $orderProduct = $item->order_product;

                    if ($variantId) {
                        $match = ($variantId == $orderProduct->variant_id);
                    } else {
                        $match = ($productId == $orderProduct->product_id);
                    }

                    if ($match) {
                        $lineItems[$item->subscription_id] = $item;
                    }
                }
            } else {
                // We have an order ID and no product ID specified, fetch all line items associated with this order.
                //
                foreach ($allOrderItems as $item) {
                    $lineItems[$item->subscription_id] = $item;
                }
            }

            if ($lineItems) {
                $collection = collect($lineItems);
            }
        }

        if (!$collection) {
            throw new InvalidLineItemsException;
        }

        return $collection;
    }

    /**
     * Run the grace process to allow decline salvage continuation
     *
     * @param Order $order
     * @param int $declinedOrderId
     * @param string $source
     * @throws \App\Exceptions\OrderAttributeImmutableException
     * @throws \billing_models\exception
     */
    public function runGraceProcess(Order $order, int $declinedOrderId, string $source = self::SOURCE_SMART_DUNNING): void
    {
        if ($declinedOrder = Order::find($declinedOrderId)) {
            // Define next counter value
            $orderAttribute     = GracePeriodCounter::forOrder($order->orders_id)->first();
            $gracePeriodCounter = 1;

            if ($orderAttribute) {
                $gracePeriodCounter = intval($orderAttribute->value) + 1;
            }

            if ($gracePeriodCounter < $order->campaign->max_grace_period) {
                // Stop recurring on the parent order
                $order->is_recurring = false;
                $order->save();

                $order->additional_products()->each(function ($upsell) {
                    $upsell->is_recurring = 0;
                    $upsell->save();
                });

                // Create new $0 order in the same salvage depth
                $newOrder                              = $declinedOrder->replicate();
                $newOrder->order_total                 = 0;
                $newOrder->currency_value              = 0;
                $newOrder->orderTotalReporting         = 0;
                $newOrder->orderTotalShippingReporting = 0;
                $newOrder->orderTotalCompleteReporting = 0;

                // Remove hold and set status to approved
                $newOrder->is_recurring  = 1;
                $newOrder->is_hold       = 0;
                $newOrder->hold_date     = '0000-00-00';
                $newOrder->orders_status = OrderStatus::STATUS_APPROVED;

                // Set next recurring date
                $subOrder                 = new bm_subscription($declinedOrderId, bm_subscription::TYPE_MAIN);
                $billByDays               = $order->common_ancestor ? $order->common_ancestor->recurring_date->format('d') : $order->recurring_date->format('d');
                $nextRecurringDate        = Carbon::createFromFormat('Y-m-d', $subOrder->get_next_recurring_date($order->recurring_date, $billByDays));
                $newOrder->recurring_date = $nextRecurringDate;
                $newOrder->date_purchased = '0000-00-00';

                // Save the new order
                $newOrder->save();

                // Update Order Total
                $newAmount      = 0;
                $currencySymbol = $order->currency->symbol_left;

                InsertOrderTotal($newOrder->orders_id, '', '', $newAmount, 'ot_total_non_taxable');
                InsertOrderTotal($newOrder->orders_id, '', '', $newAmount, 'ot_total_taxable');
                InsertOrderTotal($newOrder->orders_id, '', '', $newAmount, 'ot_tax_factor');
                InsertOrderTotal($newOrder->orders_id, 'Sales Tax:', '$salesTax%', $newAmount, 'ot_sales_tax');
                InsertOrderTotal($newOrder->orders_id, 'Flat Rate:', $currencySymbol . $newAmount, $newAmount, 'ot_shipping');
                InsertOrderTotal($newOrder->orders_id, 'Sub Total:', $currencySymbol . $newAmount, $newAmount, 'ot_subtotal');
                InsertOrderTotal($newOrder->orders_id, 'Total:', $currencySymbol . $newAmount, $newAmount, 'ot_total');

                // Create new order product
                if ($orderProduct = OrderProduct::where('orders_products_id', $declinedOrder->order_product()->first()->orders_products_id)->first()) {
                    $newOrderProduct            = $orderProduct->replicate();
                    $newOrderProduct->orders_id = $newOrder->orders_id;
                    $newOrderProduct->save();
                }

                // Create new order subscription
                if ($orderSubscription = OrderSubscription::where('order_id', $declinedOrderId)->where('type_id', OrderSubscription::TYPE_MAIN)->first()) {
                    $newOrderSubscription           = $orderSubscription->replicate();
                    $newOrderSubscription->order_id = $newOrder->orders_id;
                    $newOrderSubscription->save();
                }

                // Upsell orders
                $declinedOrder->additional_products()
                    ->each(function ($upsell) use ($newOrder, $nextRecurringDate, $newAmount, $currencySymbol) {
                        if ($upsellProduct = UpsellProduct::where('upsell_orders_products_id', $upsell->order_product()->first()->upsell_orders_products_id)->first()) {
                            // Create new $0 upsell order
                            $newUpsellOrder                              = $upsell->replicate();
                            $newUpsellOrder->main_orders_id              = $newOrder->orders_id;
                            $newUpsellOrder->order_total                 = 0;
                            $newUpsellOrder->currency_value              = 0;
                            $newUpsellOrder->orderTotalReporting         = 0;
                            $newUpsellOrder->orderTotalShippingReporting = 0;

                            // Remove hold and set status to approved for each upsell order
                            $newUpsellOrder->is_recurring  = 1;
                            $newUpsellOrder->is_hold       = 0;
                            $newUpsellOrder->hold_date     = '0000-00-00';
                            $newUpsellOrder->orders_status = OrderStatus::STATUS_APPROVED;

                            // Set next recurring date for each upsell order
                            $newUpsellOrder->recurring_date = $nextRecurringDate;
                            $newUpsellOrder->date_purchased = '0000-00-00';

                            $newUpsellOrder->save();

                            // Update Order Upsell Total
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, '', '', $newAmount, 'ot_total_non_taxable', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, '', '', $newAmount, 'ot_total_taxable', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, '', '', $newAmount, 'ot_tax_factor', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, 'Sales Tax:', '$salesTax%', $newAmount, 'ot_sales_tax', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, 'Flat Rate:', $currencySymbol . $newAmount, $newAmount, 'ot_shipping', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, 'Sub Total:', $currencySymbol . $newAmount, $newAmount, 'ot_subtotal', true);
                            InsertOrderUpsellTotal($newUpsellOrder->upsell_orders_id, 'Total:', $currencySymbol . $newAmount, $newAmount, 'ot_total', true);

                            // Create new upsell product
                            $newUpsellProduct                   = $upsellProduct->replicate();
                            $newUpsellProduct->upsell_orders_id = $newUpsellOrder->upsell_orders_id;
                            $newUpsellProduct->save();

                            // Create new upsell order subscription
                            if ($upsellOrderSubscription = OrderSubscription::where('order_id', $upsell->upsell_orders_id)->where('type_id', OrderSubscription::TYPE_UPSELL)->first()) {
                                $newUpsellOrderSubscription           = $upsellOrderSubscription->replicate();
                                $newUpsellOrderSubscription->order_id = $newUpsellOrder->upsell_orders_id;
                                $newUpsellOrderSubscription->save();
                            }
                        }
                    });

                // Create the counter in order attribute
                GracePeriodCounter::createForOrder($newOrder->orders_id, $gracePeriodCounter);

                new \gateway\processor\save_order_preservation([
                    'order_id'            => $newOrder->orders_id,
                    'parent_order_id'     => $newOrder->parent_order_id,
                    'child_order_id'      => $newOrder->child_order_id ?: $newOrder->parent_order_id,
                    'order_status'        => $newOrder->orders_status,
                    'rebill_preauth_flag' => $newOrder->int_4 ?? -1,
                    'origin'              => 'runGraceProcess'
                ]);
                
                // The following history notes are required by the analytics team
                $orderTotal = GetOrderTotal($order->common_ancestor->orders_id ? : $order->orders_id);

                OrderHistoryNote::create([
                    'order_id'    => $order->orders_id,
                    'user_id'     => User::SYSTEM,
                    'type'        => 'history-grace-period-start',
                    'status'      => "{$newOrder->orders_id}|{$orderTotal['CURRENT_TOTAL'][0]}",
                    'campaign_id' => $order->campaign->campaign_id,
                ]);

                OrderHistoryNote::create([
                    'order_id'    => $newOrder->orders_id,
                    'user_id'     => User::SYSTEM,
                    'type'        => 'amount-before-grace-period',
                    'status'      => $orderTotal['CURRENT_TOTAL'][0],
                    'campaign_id' => $newOrder->campaign->campaign_id,
                ]);

                OrderHistoryNote::create([
                    'order_id'    => $newOrder->orders_id,
                    'user_id'     => User::SYSTEM,
                    'type'        => 'history-grace-period-set',
                    'status'      => 'New Grace Period, next recurring date set to ' . UiDate($nextRecurringDate),
                    'campaign_id' => $newOrder->campaign->campaign_id,
                ]);
            } elseif ($order->history_notes->where('type', 'history-grace-period-exhausted')->count() === 0) {
                OrderHistoryNote::create([
                    'order_id'    => $order->orders_id,
                    'user_id'     => User::SYSTEM,
                    'type'        => 'history-grace-period-exhausted',
                    'status'      => 'The maximum number of allowed Graces has exhausted',
                    'campaign_id' => $declinedOrder->campaign->campaign_id,
                ]);
            }

            // If called from those sources where awaiting retry date is not removed, remove it so we don't pick it up anymore
            if (\in_array($source, [self::SOURCE_GRACE_PERIOD_JOB, self::SOURCE_REBILL_CRON, self::SOURCE_HANDLE_DECLINE], true)) {
                $order->awaiting_retry_date()->delete();
                $order->additional_products()
                    ->where('is_recurring', 1)
                    ->where('recurring_date', '<=', $order->recur_at)
                    ->each(function ($upsell) {
                        $upsell->awaiting_retry_date()->delete();
                    });
            }
        }
    }
}
