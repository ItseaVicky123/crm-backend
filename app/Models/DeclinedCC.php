<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeclinedCC
 *
 * @method static updateOrCreate(array $array, array $array)
 */
class DeclinedCC extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'declined_ccs';

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'order_id'  => 'orders_id',
        'is_upsell' => 'is_order_or_upsell',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'orders_id')
            ->where('is_order_or_upsell', 0);
    }
}
