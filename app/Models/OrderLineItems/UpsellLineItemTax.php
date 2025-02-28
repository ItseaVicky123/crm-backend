<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

class UpsellLineItemTax extends UpsellLineItem
{
    const CLASS_NAME = 'ot_line_item_sales_tax';
    const TITLE      = 'Line Item Tax:';
    const SORT_ORDER = 150;

    protected function formatText()
    {
        return $this->value . '%';
    }
}
