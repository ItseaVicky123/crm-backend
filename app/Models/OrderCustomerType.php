<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OrderCustomerType
 *
 * @package App\Models
 */
class OrderCustomerType extends Model
{
    public const
        PRIMARY       = 'primary',
        GIFT_GIVER    = 'gift_giver',
        GIFT_RECEIVER = 'gift_receiver';

    /**
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'prospect_id',
        'order_id',
        'type',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

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
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customers_id', 'customer_id');
    }

    /**
     * @return BelongsTo
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospects_id', 'prospect_id');
    }
}
