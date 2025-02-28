<?php

namespace App\Models\BillingModel;

use App\Models\BaseModel;
use App\Models\Order;
use App\Models\OrderProductBundle;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscription;
use App\Models\Upsell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasCompositePrimaryKey;
use App\Models\Offer\Offer;

/**
 * These Subscriptions are only available
 * to orders that were placed
 * using Billing Models & Offers
 *
 * Class OrderSubscription
 * @package App\Models\BillingModel
 */
class OrderSubscription extends BaseModel
{
    use HasCompositePrimaryKey;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    const TYPE_MAIN   = 1;
    const TYPE_UPSELL = 2;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $primaryKey = [
        'order_id',
        'type_id',
    ];

    /**
     * @var string
     */
    protected $table = 'billing_model_order';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'subscription_id',
        'offer_id',
        'original_offer_id',
        'billing_model_id',
        'cycles_remaining',
        'cycle_depth',
        'is_trial',
        'bill_by_type_id',
        'bill_by_days',
        'interval_day',
        'interval_week',
        'next_recurring_product',
        'next_recurring_quantity',
        'next_recurring_shipping',
        'next_shipping_id',
        'next_recurring_price',
        'next_recurring_variant',
        'preserve_quantity',
        'is_preserve_price',
        'is_prepaid',
        'is_prepaid_subscription',
        'prepaid_cycles',
        'current_prepaid_cycle',
        'next_recurring_discount_amount',
        'main_product_quantity',
        'main_product_discount_type',
        'main_product_discount_amount',
        'sticky_discount_percent',
        'sticky_discount_flat_amount',
        'billing_month',
        'billing_day',
        'buffer_days',
        'is_next_recurring_price_override',
        'updated_by',
        'created_by',
        'frequency_id'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'billing_model_id' => 'frequency_id',
        'is_trial'         => 'trial_flag',
        'created_at'       => self::CREATED_AT,
        'updated_at'       => self::UPDATED_AT,
    ];

    /**
     * Build a query with orderId and type ID
     *
     * @param $query
     * @param $orderId
     * @param bool $isMain default is true
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrder($query, $orderId, bool $isMain = true): Builder
    {
        return $query
            ->where('order_id', $orderId)
            ->where('type_id', $isMain ? Subscription::TYPE_ORDER : Subscription::TYPE_UPSELL);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model()
    {
        return $this->hasOne(BillingModel::class, 'id', 'frequency_id');
    }

    /**
     * Find best matching Billing Model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billingModelSpecial(): HasOne
    {
        return $this->hasOne(BillingModel::class, 'bill_by_days', 'bill_by_days')
            ->where('bill_by_type_id', $this->bill_by_type_id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function order()
    {
        switch ($this->type_id) {
            case self::TYPE_MAIN:
                return $this->belongsTo(Order::class, 'order_id', 'orders_id');
            case self::TYPE_UPSELL:
                return $this->belongsTo(Upsell::class, 'order_id', 'upsell_orders_id');
            default:
                return null;
        }
    }

    /**
     * @return bool
     * @todo make this distinction better
     */
    public function getIsStraightSaleAttribute()
    {
        return $this->frequency_id == 2;
    }

    /**
     * @return string|null
     */
    public function getRecurringDateAttribute()
    {
        if ($this->type_id == self::TYPE_MAIN) {
            return Order::findOrFail($this->order_id)->recurring_date;
        } elseif ($this->type_id == self::TYPE_UPSELL) {
            return Upsell::findOrFail($this->order_id)->recurring_date;
        }

        return null;
    }

    /**
     * Get the retry date explicitly.
     * @return string|null
     */
    public function getRetryDateAttribute()
    {
        $retryDate     = '0000-00-00';
        $datePurchased = null;

        if ($this->type_id == self::TYPE_MAIN) {
            $datePurchased = Order::findOrFail($this->order_id)->date_purchased;
        } elseif ($this->type_id == self::TYPE_UPSELL) {
            $datePurchased = Upsell::findOrFail($this->order_id)->date_purchased;
        }

        if ($datePurchased && strtotime($datePurchased) > 0) {
            return $datePurchased;
        }

        return $retryDate;
    }

    /**
     * @return string|null
     */
    public function getForecastedRevenueAttribute()
    {
        if ($this->type_id == self::TYPE_MAIN) {
            return Order::findOrFail($this->order_id)->forecasted_revenue;
        } elseif ($this->type_id == self::TYPE_UPSELL) {
            return Upsell::findOrFail($this->order_id)->forecasted_revenue;
        }

        return null;
    }

    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public function scopeForMainOrder(Builder $query, int $order_id)
    {
        return $query
            ->where('order_id', $order_id)
            ->where('type_id', self::TYPE_MAIN);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model_subscription_credit()
    {
        return $this->hasOne(SubscriptionCredit::class, 'id', 'subscription_id');
    }

    /**
     * @return HasOne
     */
    public function next_recurring_product(): HasOne
    {
        return $this->nextRecurringProduct();
    }

    /**
     * @return HasOne
     */
    public function nextRecurringProduct(): HasOne
    {
        return $this->hasOne(Product::class, 'products_id', 'next_recurring_product');
    }

    /**
     * @return HasOne
     */
    public function next_recurring_variant(): HasOne
    {
        return $this->hasOne(ProductVariant::class, 'id', 'next_recurring_variant');
    }

    /**
     * @param int $mainOrderId
     * @return mixed
     */
    public function next_bundle_products(int $mainOrderId)
    {
        return OrderProductBundle::where('order_id', $mainOrderId)
            ->where('bundle_id', $this->next_recurring_product)
            ->where('is_next_cycle', 1)
            ->where('is_main', ($this->type_id !== self::TYPE_MAIN ? 0 : 1));
    }

    /**
     * Using slave connection this function is checking if this Subscription is Collection Offer Type
     *
     * @param $orderId
     * @param int $typeId
     * @param bool $smcCheck
     * @return bool
     */
    public static function isCollectionSubscription($orderId, $typeId = self::TYPE_MAIN, bool $smcCheck = false): bool
    {
        if ($smcCheck && ! \system_module_control::check(\App\Facades\SMC::COLLECTIONS_OFFER)) {
            return false;
        }

        return self::readOnly()
            ->where('order_id', $orderId)
            ->where('type_id', $typeId)
            ->whereHas('offer', fn($q) => $q->where('type_id', \App\Models\Offer\Type::TYPE_COLLECTION))
            ->exists();
    }

    public static function findV2Subscription($orderId, $typeId = self::TYPE_MAIN): ?Order\Subscription
    {
        $orderSub = self::where('order_id', $orderId)
            ->where('type_id', $typeId)
            ->whereHas('offer', fn($q) => $q->where('type_id', \App\Models\Offer\Type::TYPE_COLLECTION))
            ->first();

        if ($orderSub) {
            return $orderSub->getV2Subscription();
        }

        return null;
    }

    public function getV2Subscription(): ?Order\Subscription
    {
        if ($this->offer->isCollectionType()) {
            $mainOrder = $this->order->isMain() ? $this->order : $this->order->main;

            return $mainOrder
                ->allSubscriptions()
                ->where('offer_id', $this->offer_id)
                ->where('billing_model_id', $this->billing_model_id)
                ->first();
        }

        return null;
    }
}
