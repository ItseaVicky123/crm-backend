<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateRecurringPricePreservationRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateRecurringPricePreservationRequest extends UpdateRequest
{
    /**
     * UpdateRecurringPricePreservationRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules([
                'is_preserve' => 'required|bool'
            ]),
            $this->getBaseAttributes([
                'is_preserve' => 'Is Price Preserved',
            ])
        );
    }
}
