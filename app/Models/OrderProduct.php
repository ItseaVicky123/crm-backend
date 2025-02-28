<?php

namespace App\Models;

use App\Models\BillingModel\OrderSubscription;
use App\Models\OrderLineItems\OrderProductUnitPrice;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class OrderProduct
 * @package App\Models
 */
class OrderProduct extends LineItemProduct
{
    use Eloquence, Mappable;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'orders_products';

    /**
     * @var string
     */
    protected $primaryKey = 'orders_products_id';

    /**
     * @var array
     */
    protected $guarded = [
        'orders_products_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'                => 'orders_products_id',
        'order_id'          => 'orders_id',
        'product_id'        => 'products_id',
        'category'          => 'product.category',
        'product_name'      => 'product.name',
        'sku'               => 'product.products_sku_num',
        'is_shippable'      => 'product.is_shippable',
        'price'             => 'products_price',
        'quantity'          => 'products_quantity',
        'is_fully_refunded' => 'fully_refunded_flag',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order_subscription()
    {
        return $this->hasOne(OrderSubscription::class, 'order_id', 'orders_id')
            ->where('type_id', OrderSubscription::TYPE_MAIN);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * This represents the cost of a single product for each order (it factors custom price at the moment of placing the order).
     */
    public function order_product_unit_price()
    {
        return $this->hasOne(OrderProductUnitPrice::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product_coupon_discount()
    {
        return $this->hasOne(OrderLineItems\CouponDiscountProductTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model_discount()
    {
        return $this->hasOne(OrderLineItems\BillingModelDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model_subscription_credit()
    {
        return $this->hasOne(OrderLineItems\BillingModelSubscriptionCredit::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription_hold_type()
    {
        return $this->belongsTo(SubscriptionHoldType::class, 'hold_type_id');
    }

    /**
     * @return bool
     */
    public function getIsMainAttribute()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function getIsAddOnAttribute()
    {
        return false;
    }

    /**
     * @return mixed
     * This refers to the products within a bundle that are related to an Order
     */
    public function bundle_products()
    {
        return OrderProductBundle::where('order_id', $this->order_id)
            ->where('bundle_id', $this->product_id)
            ->where('is_next_cycle', 0)
            ->where('is_main', 1);
    }

    /**
     * @return mixed
     */
    public function next_bundle_products()
    {
        return OrderProductBundle::where('order_id', $this->order_id)
            ->where('bundle_id', $this->product_id)
            ->where('is_next_cycle', 1)
            ->where('is_main', 1);
    }
}
