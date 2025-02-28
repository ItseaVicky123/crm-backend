<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class SendGatewayReceipt
 * @package App\Models\OrderAttributes
 */
class SendGatewayReceipt extends OrderAttribute
{
    const TYPE_ID = 20;
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    /**
     * @param Builder $query
     * @param int     $order_id
     * @param int     $event_id
     * @return Builder
     */
    public function scopeFiresForOrderOnEvent(Builder $query, $order_id, $event_id)
    {
        return $query
            ->where('order_id', $order_id)
            ->where('value',    $event_id);
    }
}
