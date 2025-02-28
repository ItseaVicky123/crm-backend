<?php


namespace App\Lib\Traits;

use App\Models\Order;
use App\Models\Upsell;

/**
 * A mechanism for fetching the order or upsell order model when an order_id and order_type_id column are found.
 * For models that have order_id and order_type_id.
 * Trait HasOrderByType
 * @package App\Lib\Traits
 */
trait HasOrderByType
{
    /**
     * Fetch the order or upsell order model associated with this subscription series product.
     * @return Order|Upsell
     */
    public function order()
    {
        $model = null;

        switch ($this->order_type_id) {
            case ORDER_TYPE_MAIN:
                $model = Order::findOrFail($this->order_id);
                break;
            case ORDER_TYPE_UPSELL:
                $model = Upsell::findOrFail($this->order_id);
                break;
        }

        return $model;
    }
}
