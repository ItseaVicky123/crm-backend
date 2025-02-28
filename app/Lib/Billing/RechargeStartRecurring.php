<?php

namespace App\Lib\Billing;

use App\Lib\Contexts\CycleProductRecurringDateChange;
use App\Models\Order;
/**
 * Class RechargeStartRecurring
 * @package App\Lib\Billing
 */
class RechargeStartRecurring extends \billing\recharge_main_force
{
    /**
     * RechargeStartRecurring constructor.
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        parent::__construct(['orders_id' => $orderId], true);

        if ($this->orders_id) {
            // If available on date sync is on, force update the next recurring product
            // right before billing if applicable
            //
            (new CycleProductRecurringDateChange(Order::findOrFail($this->orders_id)))
                ->performAvailableOnSync();
        }
    }
}