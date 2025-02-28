<?php

namespace App\Lib\Orders\OrderTotals\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;

/**
 * Class RecurringTotalRequest
 *
 * @package App\Lib\Orders\OrderTotals\ModuleRequests
 */
class RecurringTotalRequest extends ModuleRequest
{
    /**
     * RecurringTotalRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->handleValidation();
    }

    /**
     * Validating the request of Recurring Order Calculations.
     *
     * @throws ValidationException
     */
    protected function handleValidation(): void
    {
        $rules = [
            'order_id'           => 'required|integer|exists:mysql_slave.orders,orders_id',
            'recurring_date'     => 'sometimes|date_format:Y-m-d',
            'calculate_tax'      => 'sometimes|boolean',
            'calculate_shipping' => 'sometimes|boolean',
        ];

        $attributes = [
            'order_id'           => 'Order ID',
            'recurring_date'     => 'Recurring Date',
            'calculate_tax'      => 'Calculate Tax',
            'calculate_shipping' => 'Calculate Shipping',
        ];

        $messages = [
            'order_id.integer'           => ':attribute must be an integer',
            'order_id.exists'            => ':attribute is invalid',
            'recurring_date.date_format' => ':attribute must be in the format YYYY-MM-DD',
            'calculate_tax.boolean'      => ':attribute must be boolean',
            'calculate_shipping.boolean' => ':attribute must be boolean',
        ];

        $this->validate($rules, $attributes, $messages);
    }
}
