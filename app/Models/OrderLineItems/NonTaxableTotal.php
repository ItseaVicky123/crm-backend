<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class NonTaxableTotal extends OrderLineItem
{
    const CLASS_NAME = 'ot_total_non_taxable';
}