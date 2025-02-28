<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateNextRecurringProductRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateNextRecurringProductRequest extends UpdateRequest
{
    /**
     * UpdateNextRecurringProductRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->validate(
            $this->getBaseRules([
                'next_recurring_product' => 'required_without:next_recurring_variant|exists:mysql_slave.products,products_id',
                'next_recurring_variant' => 'required_without:next_recurring_product|exists:mysql_slave.product_variant,id',
            ]),
            $this->getBaseAttributes([
                'next_recurring_product' => 'Next Recurring Product',
                'next_recurring_variant' => 'Next Recurring Variant',
            ])
        );
    }
}