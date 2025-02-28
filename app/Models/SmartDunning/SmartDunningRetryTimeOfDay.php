<?php

namespace App\Models\SmartDunning;

use App\Models\Order;
use App\Models\Upsell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\ModelImmutable;

/**
 * Class SmartDunningRetryTimeOfDay
 * Reader for the v_smart_dunning_tod view, uses slave connection.
 * @package App\Models\SmartDunning
 */
class SmartDunningRetryTimeOfDay extends Model
{
    use ModelImmutable;

    public const CREATED_AT = null;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    protected $table = 'v_smart_dunning_tod';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('client_id', function (Builder $builder) {
            $builder->where('client_id', CRM_CLIENT_ID);
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
        return $this->belongsTo(Upsell::class, 'main_orders_id', 'order_id');
    }

    /**
     * @return BelongsTo
     */
    public function retry_date(): BelongsTo
    {
        return $this->belongsTo(SmartDunningRetryDate::class, 'order_id', 'order_id');
    }
}
