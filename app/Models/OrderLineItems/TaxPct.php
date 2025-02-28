<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class TaxPct extends OrderLineItem
{
    const CLASS_NAME = 'ot_sales_tax';

    protected function formatText()
    {
        return $this->value . '%';
    }
}