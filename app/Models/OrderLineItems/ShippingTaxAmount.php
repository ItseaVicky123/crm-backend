<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class ShippingTaxAmount extends OrderLineItem
{
    const CLASS_NAME = 'ot_shipping_tax_factor';
    const TITLE      = 'Shipping Tax Amount:';
    const SORT_ORDER = 230;
}
