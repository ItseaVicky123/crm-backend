<?php


namespace App\Models;

use App\Models\Order\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class LineItemProduct
 * @package App\Models
 */
class LineItemProduct extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'order_id',
        'product_id',
        'step_num',
        'hold_type_id',
        'promo_code_id',
        'price',
        'quantity',
        'refund_total',
        'is_fully_refunded',
        'return_quantity',
        'return_reason_id',
        'is_main',
        'is_terminal',
        'variant_id'
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'order_id',
        'product_id',
        'price',
        'quantity',
        'is_fully_refunded',
        'is_main',
    ];

    /**
     * @var string|null
     */
    protected ?string $variantName;

    /**
     * @return Product|null
     */
    public function getProductAttribute(): ?Product
    {
        return $this->product()->first();
    }

    /**
     * Get the product related to this upsell order product.
     * @return HasOne
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'products_id', 'products_id');
    }

    /**
     * Get the variant related this upsell order product.
     * @return HasOne
     */
    public function variant(): HasOne
    {
        return $this->hasOne(ProductVariant::class, 'id', 'variant_id');
    }

    /**
     * @return string
     */
    public function getVariantNameAttribute()
    {
        if (!isset($this->variantName)) {
            $implodes = [];

            if ($this->variant) {
                $this->variant->attributes()->get()->each(function($attribute) use (&$implodes) {
                    $implodes[] = $attribute->attribute ? $attribute->attribute['option']['name'] : '';
                });
            }

            $this->variantName = implode(' / ', $implodes);
        }

        return $this->variantName;
    }

    /**
     * @return mixed
     */
    public function getProductSkuAttribute()
    {
        return ($this->variant_id ? $this->variant->sku_num : $this->product->sku)
            ?? $this->product->sku;
    }

    /**
     * @return mixed
     * This factors quantity and discounts
     */
    public function getCustomPriceAttribute()
    {
        return $this->order->subtotal->value;
    }

    /**
     * @return mixed
     */
    public function getProductDiscountsAttribute()
    {
        return $this->coupon_discount +
            $this->billing_model_discount->value +
            $this->billing_model_subscription_credit->value;
    }

    /**
     * @return float|int
     */
    public function getCouponDiscountAttribute()
    {
        try {
            $price = ($this->product_coupon_discount->value * 10000)/$this->quantity/10000;
        } catch (\DivisionByZeroError $e) {
            $price = 0.00;
        }

        return $price;
    }

    /**
     * New Subscription Type. Current Order Product's Order Item
     *
     * @return HasOne
     */
    public function orderItem(): HasOne
    {
        if ($this->order) {
            return new HasOne($this->order->orderItems()->getQuery(), $this, 'product_id', 'product_id');
        }

        // Just for safety precautions, still return HasOne object but empty
        return new HasOne(OrderItem::query(), $this, '', '', '');
    }
}
