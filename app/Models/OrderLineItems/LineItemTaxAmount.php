<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class LineItemTaxAmount extends OrderLineItem
{
    const CLASS_NAME = 'ot_line_item_tax_factor';
    const TITLE      = 'Line Item Tax Amount:';
    const SORT_ORDER = 750;
}
