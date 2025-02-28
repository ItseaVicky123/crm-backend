<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class LineItemTax extends OrderLineItem
{
    const CLASS_NAME = 'ot_line_item_sales_tax';
    const TITLE      = 'Line Item Tax:';
    const SORT_ORDER = 740;

    protected function formatText()
    {
        return $this->value . '%';
    }

}
