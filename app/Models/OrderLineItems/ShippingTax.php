<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class ShippingTax extends OrderLineItem
{
    const CLASS_NAME = 'ot_shipping_sales_tax';
    const TITLE      = 'Shipping Tax:';
    const SORT_ORDER = 210;

    protected function formatText()
    {
        return $this->value . '%';
    }

}
