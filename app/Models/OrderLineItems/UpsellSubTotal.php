<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

/**
 * Class UpsellSubTotal
 * @package App\Models\OrderLineItems
 */
class UpsellSubTotal extends UpsellLineItem
{
    const CLASS_NAME = 'ot_subtotal';
    const TITLE = 'Sub Total:';
}
