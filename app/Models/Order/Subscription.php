<?php

namespace App\Models\Order;

use App\Events\Order\RecurringDateUpdated;
use App\Exceptions\CustomModelException;
use App\Lib\BillingModels\CollectionOfferHandler;
use App\Lib\LineItems\Contracts\LineItemHandlerContract;
use App\Lib\LineItems\LineItemHandler;
use App\Models\BaseModel;
use App\Models\BillingModel\BillingModel;
use App\Models\Offer\CollectionSubscription;
use App\Models\Offer\Offer;
use App\Models\Order;
use App\Models\SubscriptionHoldType;
use App\Models\OrderNoteTemplate;
use billing_models\api\billing_models_order;
use fileLogger;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Offer\Type as OfferType;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

/**
 * Class Subscription
 *
 *
 * @property-read SubscriptionLink|null $linkToChild
 * @property-read SubscriptionLink|null $linkToParent
 * @property-read Subscription|null $parent
 * @property-read Subscription|null $child
 *
 * @package App\Models\Order
 */
class Subscription extends BaseModel
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'offer_id',
        'offer_type_id',
        'billing_model_id',
        'is_completed',
        'is_active',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_child',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'is_active'    => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)->latest();
    }

    /**
     * @return BelongsTo
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo
     */
    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class);
    }

    /**
     * Offer Subscription that belongs to this Subscription
     * based on the offer type id
     *
     * @return HasOne
     * @throws \App\Exceptions\CustomModelException
     */
    public function offerSubscription(): HasOne
    {
        switch ($this->offer_type_id) {
            case OfferType::TYPE_COLLECTION:
                return $this->hasOne(CollectionSubscription::class);
            default:
                throw new CustomModelException('offer.details-unsupported-type');
        }
    }

    /**
     * Link to child subscription.
     *
     * @return HasOne
     */
    public function linkToChild(): HasOne
    {
        return $this->hasOne(SubscriptionLink::class);
    }

    /**
     * Link to parent subscription.
     *
     * @return HasOne
     */
    public function linkToParent(): HasOne
    {
        return $this->hasOne(SubscriptionLink::class, 'linked_subscription_id');
    }

    /**
     * Just so we don't get all of the orders ever created for this subscription for each product
     * We would want to get only the most recent order that was created for this subscription
     * as this will be the active one
     *
     * @return Order|null
     */
    public function mostRecentOrder(): ?Order
    {
        return $this->orderItems()->first()->order ?? null;
    }

    /**
     * @return HasOneThrough
     */
    public function parent(): HasOneThrough
    {
        return $this->hasOneThrough(
            self::class,
            SubscriptionLink::class,
            'linked_subscription_id',
            'id',
            'id',
            'subscription_id'
        );
    }

    /**
     * @return HasOneThrough
     */
    public function child(): HasOneThrough
    {
        return $this->hasOneThrough(
            self::class,
            SubscriptionLink::class,
            'subscription_id',
            'id',
            'id',
            'linked_subscription_id'
        );
    }

    public function getIsChildAttribute(): bool
    {
        return $this->parent()->exists();
    }

    /**
     * Get all order line items related to this subscription
     *
     * @param bool $onHold this will revert the results to get only those subscriptions that have been stopped
     * @return Collection
     */
    public function activeRecurringItems(bool $onHold = false): Collection
    {
        // IF: we have not been able to find anything purchased through this subscription yet
        // THEN: this subscription has only been scheduled and have not been started yet
        // THEREFORE: we will try to find these order line items through parent subscription if applicable
        if (! $order = $this->mostRecentOrder()) {
            return $this->scheduledOrderLineItems($onHold);
        }

        $orderItemsKey = $onHold ? 'all_order_items' : 'active_recurring_items';
        $lineItems     = $order->{$orderItemsKey};

        if ($onHold) {
            // Select on hold subscriptions only, this is used to resume subscription
            $lineItems = $lineItems->where('is_recurring', 0)
                ->where('is_hold', 1)
                // Select anything that has been stopped by User manually, most likely through
                ->whereIn('order_product.hold_type_id', [SubscriptionHoldType::CANCEL, SubscriptionHoldType::COMPLETE]);
        }

        return $lineItems->where('order_subscription.offer_id', $this->offer_id)
            ->where('order_subscription.billing_model_id', $this->billing_model_id);
    }

    /**
     * This method is to find these scheduled order line items that are related to this subscription
     * Because we have subscription link to the order in `order_items` table
     * And because this newly scheduled subscription has not been purchased yet (just scheduled)
     * We need to find these order line items through `parent` subscription
     * Since we can link subscription only to already started subscription that have record in `order_items` table
     *
     * @param bool $onHold this will revert the results to get only those subscriptions that have been stopped
     * @return Collection
     */
    public function scheduledOrderLineItems(bool $onHold = false): Collection
    {
        // IF: we have not found parent for this subscription
        // THEN: this subscription has not been linked to any subscription yet
        if (! $parent = $this->parent) {
            return collect();
        }

        $orderItemsKey = $onHold ? 'all_order_items' : 'active_recurring_items';
        $lineItems     = $parent
            // all order items for all orders ever created for this subscription
            ->orderItems()
            // Because one subscription could have more than one product purchased through a single order,
            // we only need first unique order id order item record
            ->groupBy('order_id')
            ->get()
            // get all active recurring items from each order record (collection of Order and Upsell objects)
            ->pluck("order.{$orderItemsKey}")
            // since these records will be grouped by unique order, flatten this array so the are all on same level
            ->flatten();

        if ($onHold) {
            // Select on hold subscriptions only, this is used to resume subscription
            $lineItems = $lineItems->where('is_recurring', 0)
                ->where('is_hold', 1)
                ->whereIn('order_product.hold_type_id', [SubscriptionHoldType::CANCEL, SubscriptionHoldType::COMPLETE]);
        }

        // filter so we get only line items related to this subscription
        return $lineItems->where('order_subscription.offer_id', $this->offer_id)
            ->where('order_subscription.billing_model_id', $this->billing_model_id)
            // group it by order ID again
            ->groupBy('order_id')
            // get only line items from the first order that matched to this subscription, which is the latest created
            ->first() ?? collect();
    }

    public function stopAllActiveRecurringItems(bool $isThroughParent = false, int $noteId = 0, ?string $noteContent = null, ?int $holdTypeId = null): bool {
        $recurringItems = $this->activeRecurringItems();

        if ($recurringItems->count()) {
            // BECAUSE: when we stop main we do swap, it messes around with subscription objects
            // to prevent issues with that we want to sort subscriptions so first we cancel upsells
            // so by the time we cancel main, we won't use these upsells
            $recurringItems = $recurringItems->sortByDesc('type_id');

            foreach ($recurringItems as $order) {
                $order->stopRecurring($holdTypeId ?? SubscriptionHoldType::CANCEL);
                fileLogger::log_flow(__METHOD__ . " - Order ID: {$order->id}. Subscription ID: {$order->subscription_id}. Recurring was stopped");
            }

            // Since all these recurring items are same order, we just need the one that is main, to fetch the first
            $order        = $recurringItems->first();
            $mainOrder    = $order->main ?? $order;
            $parentExists = $this->parent()->exists();
            $childPrefix  = $parentExists ? 'Child ' : '';
            $parentPrefix = $isThroughParent ? " due to its parent subscription been stopped" : '';
            $this->update(['is_active' => false]);

            $mainOrder->addHistoryNote(
                'history-note-subscription-stopped',
                $childPrefix . "Subscription was stopped for Offer: {$this->offer_id} and Billing Model: {$this->billing_model_id}" . $parentPrefix
            );

            //Add history not if cancel reason set
            if (! empty($noteContent)) {
                $history_type = 'notes';
                $note_obj     = OrderNoteTemplate::find($noteId);

                if ($note_obj && $note_obj->type_id > \notes\profile::TYPE_DEFAULT) {
                    $history_type = "history-note-template-{$note_obj->id}-{$note_obj->type_id}";
                }
                $mainOrder->addHistoryNote($history_type, $noteContent);
            }

            // If child subscription exists then stop it as well
            if ($this->child) {
                $this->child->stopAllActiveRecurringItems(true, 0, null, $holdTypeId);
            }

            if (! $isThroughParent && ! $parentExists){
                Event::dispatch(new \App\Events\Order\CollectionSubscriptionCancelled($mainOrder));
            }

            return true;
        }

        // If active subscriptions were found then it's inactive subscription
        $this->update(['is_active' => false]);
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function resume(): bool {
        $onHoldItems = $this->activeRecurringItems(true);

        if ($onHoldItems->count()) {
            // Begin resuming from the main, if there is one.
            $onHoldItems = $onHoldItems->sortBy('type_id');

            foreach ($onHoldItems as $order) {
                $lineItemHandler = new LineItemHandler(LineItemHandlerContract::create()->withModel($order));

                if ($lineItemHandler->resetRecurring()) {
                    fileLogger::log_flow(__METHOD__ . " - Order ID: {$order->id}. Subscription ID: {$order->subscription_id}. Recurring was successfully resumed");
                } else {
                    fileLogger::log_flow(__METHOD__ . " - Order ID: {$order->id}. Subscription ID: {$order->subscription_id}. Recurring was NOT resumed");
                }
            }

            // Since all these recurring items are same order, we just need the one that is main, to fetch the first
            $order       = $onHoldItems->first();
            $mainOrder   = $order->main ?? $order;
            $childPrefix = $this->parent()->exists() ? 'Child ' : '';
            $this->update(['is_completed' => false, 'is_active' => true]);

            $mainOrder->addHistoryNote(
                'history-note-subscription-resume',
                $childPrefix . "Subscription was resumed for Offer: {$this->offer_id} and Billing Model: {$this->billing_model_id}."
            );

            // Update next recurring information for this order
            CollectionOfferHandler::updateCollectionOfferSubscriptions($mainOrder->id);

            return true;
        }

        // If no completed/canceled subscriptions were found then it's an active subscription
        $this->update(['is_active' => true]);
        return false;
    }

    /**
     * @param string $recurringDate
     * @param bool $useNewDay TRUE by default, unless specified otherwise by the requested API
     * @return bool
     */
    public function reschedule(string $recurringDate, bool $useNewDay = true): bool
    {
        $recurringItems = $this->activeRecurringItems();

        if ($recurringItems->count()) {
            foreach ($recurringItems as $recurringItem) {
                /** If the recurring date the same, just ignore everything else, don't update */
                if ($recurringItem->next_valid_recurring_date->format('Y-m-d') === $recurringDate) {
                    return true;
                }

                $recurringItem->updateRecurringDate($recurringDate, $useNewDay);
                // Dispatch event
                Event::dispatch(new RecurringDateUpdated($recurringItem->id, $recurringItem->type_id));
            }

            $order     = $recurringItems->first();
            $mainOrder = $order->main ?? $order;

            // Format the date;
            $recurringDate = date('n/j/Y', strtotime($recurringDate));

            // Add history note
            $mainOrder->addHistoryNote(
                'history-note-changed-recurring-date',
                "Recurring date for Offer: {$this->offer_id} and Billing Model: {$this->billing_model_id} has been updated to {$recurringDate}"
            );

            if ($this->is_child) {
                // Reset announcement notification since the next product and next recurring date have been updated
                $mainOrder->scheduleAnnouncement();
                $mainOrder->addHistoryNote(
                    'history-note-announcement-scheduled',
                    "Announcement has been re-scheduled for Offer: {$this->offer_id} and Billing Model: {$this->billing_model_id}"
                );
            }

            // Update next recurring information for this order
            CollectionOfferHandler::updateCollectionOfferSubscriptions($mainOrder->id);

            return true;
        }

        return false;
    }

    /**
     * @param int $billingFrequencyId
     * @return bool
     * @throws \billing_models\exception
     * @throws \App\Exceptions\CustomModelException
     */
    public function updateFrequency(int $billingFrequencyId): bool
    {
        $recurringItems = $this->activeRecurringItems();

        if ($recurringItems->count()) {
            $oldFrequencyId = $this->billing_model_id;

            /** If the frequency ID is the same, just ignore everything else, don't update */
            if ($oldFrequencyId === $billingFrequencyId) {
                return true;
            }

            foreach ($recurringItems as $recurringItem) {
                $result = (new billing_models_order($recurringItem->id, $recurringItem->type_id))
                    ->update_billing_model($billingFrequencyId, false);

                if (! $result) {
                    throw new CustomModelException('subscription.billing-model-update-failed');
                }
            }

            $order     = $recurringItems->first();
            $mainOrder = $order->main ?? $order;

            $this->update(['billing_model_id' => $billingFrequencyId]);

            $mainOrder->addHistoryNote(
                'billing-model-updated',
                "{$this->id}:{$oldFrequencyId}:{$billingFrequencyId}"
            );

            // Update next recurring information for this order
            CollectionOfferHandler::updateCollectionOfferSubscriptions($mainOrder->id);

            return true;
        }

        return false;
    }
}
