<?php

namespace App\Models;

use App\Lib\HasCreator;
use App\Lib\Lime\LimeSequencer;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Campaign\Campaign;
use CouponHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Coupon
 * @package App\Models
 */
class Coupon extends Model
{
    use LimeSoftDeletes, LimeSequencer, HasCreator, Eloquence, Mappable;

    const TYPE_ORDER        = CouponType::ORDER;
    const TYPE_PRODUCT      = CouponType::PRODUCT;
    const DISC_TYPE_FLAT    = CouponDiscountType::FLAT;
    const DISC_TYPE_PCT     = CouponDiscountType::PERCENT;
    const BEHAVIOR_PRODUCT  = CouponDiscountBehaviorType::PRODUCT;
    const BEHAVIOR_TOTAL    = CouponDiscountBehaviorType::TOTAL;
    const BEHAVIOR_SHIPPING = CouponDiscountBehaviorType::SHIPPING;

    const CREATED_AT = 'create_in';
    const UPDATED_AT = 'update_in';
    const CREATED_BY = 'create_id';
    const UPDATED_BY = 'update_id';

    /**
     * @var string
     */
    protected $table = 'coupon';

    /**
     * @var array
     */
    protected $dates = [
        'create_in',
        'update_in',
        'expiration_date',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'type_id',
        'name',
        'description',
        'discount_amt',
        'discount_pct',
        'discount_amount',
        'discount_percent',
        'is_free_shipping',
        'minimum_purchase',
        'min_qty',
        'max_qty',
        'minimum_quantity',
        'maximum_quantity',
        'is_bogo',
        'is_buy_x_get_y',
        'is_lifetime',
        'use_count',
        'promo_code_count',
        'product_id',
        'variant_id',
        'limits',
        'expires_at',
        'is_active',
        'created_at',
        'updated_at',
        'creator',
        'updator',
        'products',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'discount_amount',
        'discount_percent',
        'minimum_quantity',
        'maximum_quantity',
        'is_active',
        'created_at',
        'updated_at',
        'expires_at',
        'is_free_shipping',
        'promo_code_count',
        'limits',
        'creator',
        'updator',
        'products',
        'eligibleProducts',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'         => 'active',
        'created_by'        => self::CREATED_BY,
        'updated_by'        => self::UPDATED_BY,
        'created_at'        => self::CREATED_AT,
        'updated_at'        => self::UPDATED_AT,
        'is_free_shipping'  => 'free_shipping_flag',
        'total_use_count'   => 'use_count',
        'behavior_id'       => 'attribute_discount_behavior_id',
        'discount_type_id'  => 'attribute_discount_type_id',
        'discount_amount'   => 'discount_amt',
        'discount_percent'  => 'discount_pct',
        'minimum_quantity'  => 'min_qty',
        'maximum_quantity'  => 'max_qty',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'type_id',
        'name',
        'description',
        'created_by',
        'updated_by',
        'is_free_shipping',
        'total_use_count',
        'behavior_id',
        'discount_type_id',
        'discount_amount',
        'discount_percent',
        'minimum_quantity',
        'maximum_quantity',
        'is_active',
        'is_lifetime',
        'max_use',
        'customer_use',
        'limit_code_global',
        'limit_code_user',
        'minimum_purchase',
        'product_id',
        'variant_id',
        'expiration_date',
        'timezone',
        'is_bogo',
        'is_buy_x_get_y',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coupon) {
            $coupon->id = $coupon::fetchNextSequence();
            $coupon->is_active = 1;

            if (!$coupon->created_by) {
                $coupon->created_by = get_current_user_id();
            }

            if (!$coupon->updated_by) {
                $coupon->updated_by = $coupon->created_by;
            }

            // Default to 0 if not specified
            if ($coupon->discount_amount === null) {
                $coupon->discount_amount = 0;
            }

            // Default to 0 if not specified
            if ($coupon->discount_percent === null) {
                $coupon->discount_percent = 0;
            }

            if ($coupon->type_id == self::TYPE_PRODUCT && $coupon->product_id && $coupon->variant_id) {
                $coupon->product_key = sprintf('%d:%d',
                    (int) $coupon->product_id,
                    (int) $coupon->variant_id
                );
            }
        });

        static::deleting(function($coupon) {
            $coupon->promo_codes()->detach();
            $coupon->campaigns()->detach();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function promo_codes()
    {
        return $this->belongsToMany(PromoCode::class, 'coupon_promo_code_jct')
            ->withPivotValue('active', 1);
    }

    /**
     * @return HasMany
     */
    public function products()
    {
        return $this->hasMany(CouponProduct::class, 'coupon_id')
            ->where('is_eligible', false);
    }

    /**
     * @return HasMany
     */
    public function eligibleProducts(): HasMany
    {
        return $this->hasMany(CouponProduct::class, 'coupon_id')
            ->where('is_eligible', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsAttribute()
    {
        return $this->products()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'coupon_campaign_jct', 'coupon_id', 'campaign_id')
            ->withoutGlobalScope('campaign_permissions');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(CouponType::class, 'type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discount_type()
    {
        return $this->belongsTo(CouponDiscountType::class, 'attribute_discount_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discount_behavior_type()
    {
        return $this->belongsTo(CouponDiscountBehaviorType::class, 'attribute_discount_behavior_id');
    }

    /**
     * If expires, must set timezone
     *
     * @return \Carbon\Carbon | null
     */
    public function getExpiresAtAttribute()
    {
        if ($this->expiration_date->year > 0) {
            $this->setAttribute('expires_at', $this->expiration_date->timezone($this->timezone));

            return $this->attributes['expires_at'];
        }

        return null;
    }

    /**
     * @return array | null
     */
    public function getLimitsAttribute()
    {
        if ($this->max_use + $this->customer_use + $this->limit_code_global + $this->limit_code_user > 0) {
            $this->setAttribute('limits', collect([
                'total'                 => $this->max_use,
                'per_customer'          => $this->customer_use,
                'per_code'              => $this->limit_code_global,
                'per_code_per_customer' => $this->limit_code_user,
           ]));
        }

        return $this->attributes['limits'] ?? null;
    }

    /**
     * @return int
     */
    public function getPromoCodeCountAttribute()
    {
        return $this->promo_codes()->count();
    }

    /**
     * Determine whether or not the coupon discount amount type is a flat amount.
     * @return bool
     */
    public function isFlatAmount(): bool
    {
        return $this->discount_type_id == self::DISC_TYPE_FLAT;
    }

    /**
     * Determine whether or not the coupon discount amount type is a percentage.
     * @return bool
     */
    public function isPercent(): bool
    {
        return $this->discount_type_id == self::DISC_TYPE_PCT;
    }

    /**
     * Determine whether or not the coupon discount type is a product.
     * @return bool
     */
    public function isProductType(): bool
    {
        return $this->type_id == self::TYPE_PRODUCT;
    }

    /**
     * Determine whether or not the coupon discount type is an order.
     * @return bool
     */
    public function isOrderType(): bool
    {
        return $this->type_id == self::TYPE_ORDER;
    }

    /**
     * Determine whether or not the coupon discount behavior is a product.
     * @return bool
     */
    public function isProductBehavior(): bool
    {
        return $this->attribute_discount_behavior_id == self::BEHAVIOR_PRODUCT;
    }

    /**
     * Determine whether or not the coupon discount behavior is a total.
     * @return bool
     */
    public function isTotalBehavior(): bool
    {
        return $this->attribute_discount_behavior_id == self::BEHAVIOR_TOTAL;
    }

    /**
     * Determine whether or not the coupon discount behavior is a shipping.
     * @return bool
     */
    public function isShippingBehavior(): bool
    {
        return $this->attribute_discount_behavior_id == self::BEHAVIOR_SHIPPING;
    }

    /**
     * Determine whether or not the coupon discount behavior for each product.
     * @return bool
     */
    public function isForEachProduct(): bool
    {
        return $this->isProductType() || $this->isProductBehavior();
    }

    /**
     * Calculate shipping amount after applying all discounts
     * @param float $amount
     * @return float
     */
    public function calculateDiscountedShippingAmount(float $amount): float
    {
        if ($this->isShippingBehavior()) {
            $amount = $this->calculateDiscountedAmount($amount);
        } else if ($this->is_free_shipping) {
            $amount = 0.0;
        }

        return max(0, $amount);
    }

    /**
     * Calculate discount
     * @param float $amount
     * @return float
     */
    public function calculateDiscount(float $amount): float
    {
        $discount = 0;
        if ($this->isFlatAmount()) {
            $discount = $this->discount_amount;
        } else if ($this->isPercent()) {
            $discount = $amount * $this->discount_percent / 100;
        }

        return $discount;
    }

    /**
     * Calculate amount after applying discounts
     * @param float $amount
     * @return float
     */
    public function calculateDiscountedAmount(float $amount): float
    {
        return max(0, $amount - $this->calculateDiscount($amount));
    }
}
