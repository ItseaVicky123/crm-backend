<?php

namespace App\Models\SmartDunning;

use App\Models\Order;
use App\Models\Upsell;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @property $subscription_id
 */
class SmartDunningRetryDate extends Model
{
    use Eloquence, Mappable, HasCompositePrimaryKey;

    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'smart_dunning_retry_date';

    /**
     * @var array
     */
    public $primaryKey = [
        'declined_order_id',
        'order_id',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'is_used',
        'updated_at',
    ];

    public static function boot(): void
    {
        static::addGlobalScope('is_used', function (Builder $builder) {
            $builder->where('is_used', 0);
        });
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orders_id', 'order_id');
    }

    /**
     * @return BelongsTo
     */
    public function upsell(): BelongsTo
    {
        return $this->belongsTo(Upsell::class, 'main_orders_id', 'order_id')
            ->where('subscription_id', $this->subscription_id);
    }

    /**
     * @return HasOne
     */
    public function declined_order(): HasOne
    {
        return $this->hasOne(Order::class, 'orders_id', 'declined_order_id');
    }

    public function time_of_day(): HasOne
    {
        return $this->hasOne(SmartDunningRetryTimeOfDay::class, 'order_id', 'order_id');
    }
}
