<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

/**
 * Class UpsellPrepaidDiscount
 * @package App\Models\OrderLineItems
 */
class UpsellPrepaidDiscount extends UpsellLineItem
{
    const CLASS_NAME = 'ot_prepaid_discount';
    const TITLE = 'Prepaid Discount:';
}
