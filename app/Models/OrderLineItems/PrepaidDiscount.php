<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class PrepaidDiscount extends OrderLineItem
{
    const CLASS_NAME = 'ot_prepaid_discount';
    const TITLE = 'Prepaid Discount:';
}
