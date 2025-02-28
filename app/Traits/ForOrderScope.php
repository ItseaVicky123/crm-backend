<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait ForOrderScope
 * @package App\Traits
 */
trait ForOrderScope
{
    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public function scopeForOrder(Builder $query, $order_id): Builder
    {
        return $query->where('orders_id', $order_id);
    }

    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public static function scopeForOrderId(Builder $query, $order_id): Builder
    {
        return $query->where('order_id', $order_id);
    }
}
