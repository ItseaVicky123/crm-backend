<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

class UpsellLineItemTaxAmount extends UpsellLineItem
{
    const CLASS_NAME = LineItemTaxAmount::CLASS_NAME;
    const TITLE      = LineItemTaxAmount::TITLE;
    const SORT_ORDER = 750;
}
