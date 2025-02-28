<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;
use App\Models\ProductVariant;
use App\Lib\HasCreator;
use App\Lib\Traits\HasOrderByType;

/**
 * Class SubscriptionSeriesProduct
 * @package App\Models\Offer
 */
class SubscriptionSeriesProduct extends BaseModel
{
    use HasCreator;
    use HasOrderByType;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'subscription_series_id',
        'order_id',
        'order_type_id',
        'product_id',
        'variant_id',
    ];

    /**
     * Instance hooks on boot. Just set the admin to system by default.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            $instance->created_by = get_current_user_id();
        });
        static::updating(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
    }

    /**
     * Fetch the subscription series that owns this subscription series product.
     * @return BelongsTo
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(SubscriptionSeries::class, 'subscription_series_id');
    }

    /**
     * Fetch the product that belongs to this subscription series product
     * @return HasOne
     */
    public function product(): HasOne
    {
        return $this->hasOne(\App\Models\Product::class, 'product_id', 'products_id');
    }

    /**
     * Fetch the variant that belongs to this subscription series product
     * @return HasOne
     */
    public function productVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class, 'variant_id', 'id');
    }
}
