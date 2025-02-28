<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateNextRecurringQuantityRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateNextRecurringQuantityRequest extends UpdateRequest
{
    /**
     * UpdateNextRecurringQuantityRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules(['next_recurring_quantity' => 'required|int|min:1']),
            $this->getBaseAttributes(['next_recurring_quantity' => 'Next Recurring Quantity'])
        );
    }
}
