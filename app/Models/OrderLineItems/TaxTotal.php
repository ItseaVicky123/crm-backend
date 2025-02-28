<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class TaxTotal extends OrderLineItem
{
    const CLASS_NAME = 'ot_tax_factor';
    const TITLE = 'Sales Tax:';
}