<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DeclineEvent
 *
 * @method static create(array $array)
 */
class DeclineEvent extends BaseModel
{
    const CREATED_AT = 'date_in';

    const UPDATED_AT = null;

    public const DECLINE_SALVAGE = 1;

    /**
     * @var string
     */
    protected $table = 'decline_event';

    /**
     * @var array
     */
    protected $fillable = [
        'date_in',
        'order_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orders_id', 'order_id');
    }
}
