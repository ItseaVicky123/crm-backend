<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\BaseModel;
use App\Lib\HasCreator;
use App\Lib\Traits\HasOrderByType;

/**
 * Class SubscriptionSeries
 * @package App\Models\Offer
 */
class SubscriptionSeries extends BaseModel
{
    use HasCreator;
    use HasOrderByType;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'offer_id',
        'order_id',
        'order_type_id',
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
     * Fetch the offer that belongs to this subscription series.
     * @return HasOne
     */
    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class, 'offer_id', 'id');
    }

    /**
     * Fetch
     * @return HasMany
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(SubscriptionSeriesProduct::class, 'subscription_series_id', 'id');
    }
}
