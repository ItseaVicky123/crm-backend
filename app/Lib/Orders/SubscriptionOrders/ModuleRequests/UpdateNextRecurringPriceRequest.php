<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateNextRecurringPriceRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateNextRecurringPriceRequest extends UpdateRequest
{
    /**
     * UpdateNextRecurringPriceRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules([
                'price'       => 'required|numeric|min:0|max:999999',
                'is_preserve' => 'bool'
            ]),
            $this->getBaseAttributes([
                'price'       => 'Next Recurring Price',
                'is_preserve' => 'Is Price Preserved',
            ])
        );
    }
}
