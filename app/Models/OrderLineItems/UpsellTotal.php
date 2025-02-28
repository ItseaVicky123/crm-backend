<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

/**
 * Class UpsellTotal
 * @package App\Models\OrderLineItems
 */
class UpsellTotal extends UpsellLineItem
{
    const CLASS_NAME = 'ot_total';
    const TITLE = 'Total:';
}
