<?php

namespace App\Models;

use App\Events\Subscription\SubscriptionUpdated;
use App\Models\BillingModel\BillingModel;
use App\Models\BillingModel\OrderSubscription;
use App\Models\Order\OrderItem;
use App\Models\OrderAttributes\Announcement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\Payment\ContactPaymentSource;

/**
 * Class Subscription
 * @package App\Models
 *
 * @property-read ?Carbon $next_valid_recurring_date the next recurring date for the current order or upsell as a
 *                                                   Carbon object, IF the stored value is a valid date
 */
class Subscription extends BaseModel
{

    public const TYPE_ORDER  = 1;
    public const TYPE_UPSELL = 2;
    public const EMPTY_DATE  = '0000-00-00';

    /**
     * @param $subscriptionId
     * @return mixed
     */
    public function findBySubscriptionId($subscriptionId)
    {
        return Upsell::where('subscription_id', $subscriptionId)
            ->orderBy('created_at', 'DESC')
            ->first()
            ?? Order::where('subscription_id', $subscriptionId)
                ->orderBy('created_at', 'DESC')
                ->first();
    }

    /**
     * @param $subscriptionId
     * @param $orderId
     * @param bool $useSlave
     * @return mixed
     */
    public function findLineItemBySubscriptionId($subscriptionId, $orderId, bool $useSlave = false)
    {
        return Upsell::readOnly($useSlave)
            ->where('subscription_id', $subscriptionId)
                     ->where('order_id', $orderId)
                     ->orderBy('created_at', 'DESC')
                     ->first()
            ?? Order::readOnly($useSlave)
                ->where('subscription_id', $subscriptionId)
                    ->where('id', $orderId)
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Find main or upsell line item by the order and type IDs
     *
     * @param int $orderId
     * @param int $typeId
     * @param bool $useSlave
     * @return mixed
     */
    public static function findLineItemByTypeAndId(int $orderId, int $typeId = self::TYPE_ORDER, bool $useSlave = true)
    {
        $modelClass = $typeId === self::TYPE_ORDER ? Order::class : Upsell::class;

        return $modelClass::readOnly($useSlave)->find($orderId);
    }

    /**
     * @param string $subscriptionId
     * @param bool $filterByStatus
     * @return mixed
     */
    public function getSubscriptionById(string $subscriptionId, bool $filterByStatus = true)
    {
        $orderStatuses = [OrderStatus::STATUS_APPROVED, OrderStatus::STATUS_VOID, OrderStatus::STATUS_SHIPPED];

        return Order::with(
            'order_product', 'order_product.product', 'order_product.product.meta',
            'order_subscription', 'order_subscription.billing_model', 'ship_country'
            )
                ->when($filterByStatus, fn ($q) => $q->whereIn('status_id', $orderStatuses))
                ->where('subscription_id', $subscriptionId)
                ->orderBy('created_at', 'DESC')
                ->first() ??
            Upsell::with(
                'main', 'order_product', 'order_product.product', 'order_product.product.meta',
                'order_subscription', 'order_subscription.billing_model', 'ship_country'
            )
                ->when($filterByStatus, fn ($q) => $q->whereIn('status_id', $orderStatuses))
                ->where('subscription_id', $subscriptionId)
                ->orderBy('created_at', 'DESC')
                ->first();
    }

    /**
     * @return array
     */
    protected function getShippingArrayAttribute(): array
    {
        return [
            'first_name'   => $this->customers_fname,
            'last_name'    => $this->customers_lname,
            'address'      => $this->customers_street_address,
            'address2'     => $this->customers_suburb,
            'city'         => $this->customers_city,
            'state'        => $this->customers_state,
            'zip'          => $this->customers_postcode,
            'country'      => $this->ship_country->name,
            'country_iso2' => $this->ship_country->iso_2,
        ];
    }

    /**
     * @return HasOne
     */
    public function ship_country(): HasOne
    {
        return $this->hasOne(Country::class, 'countries_id', 'delivery_country');
    }

    /**
     * @return string
     */
    public function getShippingCountryIso2Attribute()
    {
        if (isset($this->shipping_country_iso2) && $this->shipping_country_iso2) {
            return $this->shipping_country_iso2;
        }

        if (isset($this->delivery_country) && $this->delivery_country) {
            return Country::readOnly()->where('countries_id', $this->delivery_country)->first()->iso_2 ?? 'US';
        }

        return 'US';
    }

    /**
     * @return string
     */
    public function getLegacyStatusAttribute(): string
    {
        switch (true) {
            case $this->is_recurring == 1:
                if ($this->retry_at->year > 0) {
                    $status = LegacySubscription::STATUS_RETRYING;
                } else {
                    $status = LegacySubscription::STATUS_ACTIVE;
                }
            break;
            case $this->hold_type_id == SubscriptionHoldType::MERCHANT:
                $status = LegacySubscription::STATUS_PAUSED;
            break;
            default:
                $status = LegacySubscription::STATUS_CANCELLED;
            break;
        }

        return $status;
    }

    /**
     * Get Subscription Status
     *
     * @return string
     */
    public function getSubscriptionStatusAttribute(): string
    {
        $status = $this->legacy_status;

        // If this subscirption was set as `Canceled`, then it's not recurring and not paused, if not on hold then it's `Completed`
        if ($status === LegacySubscription::STATUS_CANCELLED && ! $this->is_hold && ! $this->is_hold_type_id) {
            $status = 'completed';
        }

        return $status;
    }

    /**
    * Consolidation of getting subscription order from either upsell or orders table
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
   public function subscription_order()
   {
       return $this->order_subscription();
   }

    /**
     * @param  float $creditAmount
     * @param  int   $userId
     * @param  bool  $replaceCredit
     * @param  book  $skipNote
     * @return bool
     */
    public function issueBillingModelSubscriptionCreditInternal(float $creditAmount, int $userId, bool $replaceCredit = false, bool $skipNote = false): bool
    {
        // Parent must have its own implementation of subscription_order()
        if (
            $this->subscription_order &&
            $creditModel = $this->subscription_order->billing_model_subscription_credit()->first()
        ) {
            // If we're replacing, use requested amount, otherwise calculate new amount. Then update credit model.
            $requestAmount   = number_format($creditAmount, 2, '.', '');
            $creditAvailable = $replaceCredit ? $requestAmount : ((float) $requestAmount + (float) $creditModel->available_credit);
            $creditModel->update(['available_credit' => $creditAvailable]);

            // If it comes from a gateway object, skip the note as the credit has been reapplied to the
            // parent order since the child order was declined and didn't use subscription credit.
            //
            if ($skipNote) {
                return true;
            }

            // Append a note to order history
            $creditAvailable = number_format($creditAvailable, 2, '.', '');
            $this->createHistoryNote(
                "{$requestAmount}:{$creditAvailable}",
                'subscription-credit-issued',
                $userId
            );

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isShippable()
    {
        return (($this->is_shippable != '') ? $this->is_shippable : $this->order_product->product->is_shippable);
    }

    /**
     * Encapsulate history note creation on the billing_model_order level
     * @param string $message
     * @param string $type
     * @param int $author
     */
    public function createHistoryNote(string $message, string $type = 'note', int $author = SYSTEM_USER_ID): void
    {
        $orderId = $this->id;
        if ($this instanceof Upsell) {
            $orderId = $this->main_order_id;
        }
        $this->history_notes()->create([
            'order_id'  => $orderId,
            'message'   => $message,
            'type_name' => $type,
            'author'    => $author,
        ]);
    }

    /**
     * @return mixed
     */
    public function getSmartDunningRetryDateRecordAttribute()
    {
        return $this->smart_dunning_retry_date()->where('is_used', 0)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getIsAwaitingRetryDateAttribute()
    {
        return $this->awaiting_retry_date()->exists();
    }

    /**
     * Get the next recurring date from the current order or upsell as a Carbon object IF the stored value
     * is a valid date
     * @return \Carbon\Carbon|null
     */
    protected function getNextValidRecurringDateAttribute(): ?Carbon
    {
        $date = null;
        if ($this->date_purchased && strtotime($this->date_purchased) > 0) {
            $date = $this->retry_at;
        } else if ($this->recurring_date && strtotime($this->recurring_date) > 0) {
            $date = $this->recur_at;
        }
        if ($date && !$date instanceof Carbon) {
            $message = "Order has recurring date but helper attributes are not Carbon objects, this should never happen.";
            Log::debug($message, ['orderDetails' => $this->toArray()]);
            $date = null;
        }
        return $date;
    }

    /**
     * Get the order model order type ID.
     * @return int|null
     */
    public function getOrderTypeId(): ?int
    {
        if ($this instanceof Order) {
            return ORDER_TYPE_MAIN;
        } else if ($this instanceof Upsell) {
            return ORDER_TYPE_UPSELL;
        }

        return null;
    }

    /**
     * Determine if the current instance is main orders table or not.
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->getOrderTypeId() == ORDER_TYPE_MAIN;
    }

    /**
     * @param int $productId
     * @param int|null $variantId
     * @param bool $removeVariant
     * @return bool
     */
    public function updateNextRecurringProduct(int $productId, ?int $variantId = null, bool $removeVariant = false): bool
    {
        $success = false;

        if ($billingModelOrder = $this->subscription_order) {
            $updates = ['next_recurring_product' => $productId];

            if ($variantId) {
                $updates['next_recurring_variant'] = $variantId;
            }

            if ($removeVariant) {
                $updates['next_recurring_variant'] = 0;
            }

            $success = $billingModelOrder->update($updates);
        }

        if ($success && ValueAddService::isEnabled(\value_add_service_entry::BIGCOMMERCE)) {
            Event::dispatch(new SubscriptionUpdated(['product.id'], $this));
        }

        return $success;
    }

    /**
     * @param int $quantity
     * @return bool
     */
    public function updateNextRecurringQuantity(int $quantity): bool
    {
        $success = false;

        if ($billingModelOrder = $this->subscription_order) {
            $success = $billingModelOrder->update(['next_recurring_quantity' => $quantity]);
        }

        if ($success && ValueAddService::isEnabled(\value_add_service_entry::BIGCOMMERCE)) {
            Event::dispatch(new SubscriptionUpdated(['product.quantity'], $this));
        }

        return $success;
    }

    /**
     * @param float $price
     * @param int|null $isPreserve
     * @return bool
     */
    public function updateNextRecurringPrice(float $price, ?int $isPreserve = null, ?int $isPriceOverride = null): bool
    {
        $success = false;

        if ($billingModelOrder = $this->subscription_order) {
            $updates = ['next_recurring_price' => $price];

            if (!is_null($isPreserve)) {
                $updates['is_preserve_price'] = $isPreserve;
            }

            if (!is_null($isPriceOverride)) {
                $updates['is_next_recurring_price_override'] = $isPriceOverride;
            }

            $success = $billingModelOrder->update($updates);
        }

        return $success;
    }

    /**
     * @param int $isPreserve
     * @return bool
     */
    public function updatePricePreservation(int $isPreserve): bool
    {
        $success = false;

        if ($billingModelOrder = $this->subscription_order) {
            $success = $billingModelOrder->update(['is_preserve_price' => $isPreserve]);
        }

        return $success;
    }

    /**
     * @param int $billingModelId
     * @return bool
     */
    public function updateRecurringBillingModel(int $billingModelId): bool
    {
        $success = false;

        if ($billingModelOrder = $this->subscription_order) {
            $billingModel = BillingModel::find($billingModelId);
            $offer        = $billingModelOrder->offer;
            $updates      = [
                'frequency_id'    => $billingModel->id,
                'bill_by_type_id' => $billingModel->bill_by_type_id,
                'bill_by_days'    => $billingModel->bill_by_days,
                'interval_day'    => $billingModel->interval_day,
                'interval_week'   => $billingModel->interval_week,
                'buffer_days  '   => $billingModel->buffer_days,
            ];

            // Carry over billing model discounts if exists
            //
            if ($discount = $offer->billingModelDiscounts()->where('frequency_id', $billingModelId)->first()) {
                $updates['sticky_discount_percent']     = 0;
                $updates['sticky_discount_flat_amount'] = 0;

                if ($discount->percent) {
                    $updates['sticky_discount_percent'] = $discount->percent;
                } else {
                    $updates['sticky_discount_flat_amount'] = $discount->amount;
                }
            }

            $success = $billingModelOrder->update($updates);

            if ($success && ValueAddService::isEnabled(\value_add_service_entry::BIGCOMMERCE)) {
                Event::dispatch(new \App\Events\Subscription\SubscriptionUpdated(['frequency'], $this));
            }
        }

        return $success;
    }

    /**
     * Idempotent operation to set (create or update) my Address override
     *
     * @param \App\Models\Address $address
     * @param string              $subscriptionId
     * @return \App\Models\SubscriptionOverride
     */
    public function setAddressOverride(Address $address, string $subscriptionId): SubscriptionOverride
    {
        // create address override, update if one exists
        return SubscriptionOverride::updateOrCreate(
            ['subscription_id' => $subscriptionId],
            ['address_id' => $address->id]
        );
    }

    /**
     * @return int|null
     */
    public function getHoldTypeIdAttribute(): ?int
    {
        return $this->order_product()->first()->hold_type_id;
    }

    /**
     * Idempotent operation to set (create or update) Payment source override
     *
     * @param ContactPaymentSource $contactPaymentSource
     * @param string               $subscriptionId
     * @return SubscriptionOverride
     */
    public function setPaymentSourceOverride(ContactPaymentSource $contactPaymentSource, string $subscriptionId): SubscriptionOverride
    {
        // create payment source override, update if one exists
        return SubscriptionOverride::updateOrCreate(
            ['subscription_id' => $subscriptionId],
            ['contact_payment_source_id' => $contactPaymentSource->id]
        );
    }

    /**
     * @return mixed
     */
    public function getLastDeclineAttribute()
    {
        $main = $this->isMain() ? $this : $this->main;

        return $main->children()->where('orders_status', OrderStatus::STATUS_DECLINED)->latest()->first();
    }

    /**
     * New Subscription Type. Order Items
     *
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    /**
     * Set the announcement status as scheduled.
     *
     * @return Announcement
     */
    public function scheduleAnnouncement(): Announcement
    {
        return $this->announcement()->updateOrCreate([], ['value' => Announcement::SCHEDULED]);
    }

    /**
     * Set the announcement status as announced.
     *
     * @return Announcement
     */
    public function markAsAnnounced(): Announcement
    {
        return $this->announcement()->updateOrCreate([], ['value' => Announcement::ANNOUNCED]);
    }

    /**
     * Get main order based on current subscription object
     *
     * @return \App\Models\Order
     */
    public function getMainOrder(): Order
    {
        return $this instanceof Order ? $this : $this->main;
    }

    /**
     * Determine Next Recurring Product
     *
     * @return \App\Models\Product|null
     */
    public function getNextProductAttribute(): ?Product
    {
        if ($this->subscription_order) {
            return $this->subscription_order->nextRecurringProduct;
        }

        $orderProduct = $this->order_product;
        $product      = $orderProduct->product ?? null;

        if (! ($orderProduct->is_add_on ?? false) && $product && $product->recur_product_id > 0) {
            // Use product override if applicable otherwise use recurring product set on product itself
            return Product::readOnly()->find($this->custom_rec_prod_id ?: $product->recur_product_id);
        }

        // Use current product as default then
        return $product;
    }

    /**
     * Determine Next Recurring Quantity
     *
     * @return int|null
     */
    public function getNextQuantityAttribute(): ?int
    {
        if ($this->subscription_order) {
            return $this->subscription_order->next_recurring_quantity;
        }

        $product = $this->next_product;

        if ($product->is_qty_preserved ?? false) {
            return $this->order_product->quantity ?? 1;
        }

        return $product ? 1 : null;
    }

    /**
     * Get a list of all subscription IDs based on the order id passed
     *
     * @param int $orderId
     * @return array
     */
    public static function getSubscriptionIdsByOrderId(int $orderId): array
    {
        return Order::readOnly()
            ->select('subscription_id')
            ->where('orders_id', $orderId)
            ->union(
                Upsell::select('subscription_id')
                    ->where('main_orders_id', $orderId)
            )
            ->pluck('subscription_id')
            ->toArray() ?? [];
    }
}
