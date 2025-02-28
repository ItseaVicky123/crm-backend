<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateNextRecurringDateRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateNextRecurringDateRequest extends UpdateRequest
{
    /**
     * UpdateNextRecurringDateRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules(['date' => 'required|date_format:Y-m-d|after:yesterday']),
            $this->getBaseAttributes(['date' => 'New Recurring Date'])
        );
    }
}
