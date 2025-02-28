<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateBillingModelRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateBillingModelRequest extends UpdateRequest
{
    /**
     * UpdateBillingModelRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules(['billing_model_id' => 'required|exists:mysql_slave.billing_frequency,id']),
            $this->getBaseAttributes(['billing_model_id' => 'Billing Model ID'])
        );
    }
}
