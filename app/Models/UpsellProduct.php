<?php

namespace App\Models;

use App\Models\BillingModel\OrderSubscription;
use App\Models\OrderProductUnitPrice;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class UpsellProduct
 * @package App\Models
 */
class UpsellProduct extends LineItemProduct
{
    use Eloquence, Mappable;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'upsell_orders_products';

    /**
     * @var string
     */
    protected $primaryKey = 'upsell_orders_products_id';

    /**
     * @var array
     */
    protected $guarded = [
        'upsell_products_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'                => 'upsell_orders_products_id',
        'order_id'          => 'upsell_orders_id',
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
        return $this->belongsTo(Upsell::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order_subscription()
    {
        return $this->hasOne(OrderSubscription::class, 'order_id', 'upsell_orders_id')
            ->where('type_id', OrderSubscription::TYPE_UPSELL);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * This represents the cost of a single product for each order (it factors custom price at the moment of placing the order).
     */
    public function order_product_unit_price()
    {
        return $this->hasOne(UpsellProductUnitPrice::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return bool
     */
    public function getIsMainAttribute()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getIsAddOnAttribute()
    {
        return (bool) $this->order->is_add_on;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product_coupon_discount()
    {
        return $this->hasOne(OrderLineItems\UpsellCouponDiscountProductTotal::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model_discount()
    {
        return $this->hasOne(OrderLineItems\UpsellBillingModelDiscount::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model_subscription_credit()
    {
        return $this->hasOne(OrderLineItems\UpsellBillingModelSubscriptionCredit::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return mixed
     * This refers to the products within a bundle that are related to an Order
     */
    public function bundle_products()
    {
        return OrderProductBundle::where('order_id', $this->order->order_id)
            ->where('bundle_id', $this->product_id)
            ->where('is_next_cycle', 0)
            ->where('is_main', $this->is_main);
    }

    /**
     * @return mixed
     */
    public function next_bundle_products()
    {
        return OrderProductBundle::where('order_id', $this->order->order_id)
             ->where('bundle_id', $this->product_id)
             ->where('is_next_cycle', 1)
             ->where('is_main', $this->is_main);
    }
}
