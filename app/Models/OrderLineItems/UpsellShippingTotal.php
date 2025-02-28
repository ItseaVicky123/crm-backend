<?php

namespace App\Models\OrderLineItems;

use App\Models\UpsellLineItem;

/**
 * Class UpsellShippingTotal
 * @package App\Models\OrderLineItems
 */
class UpsellShippingTotal extends UpsellLineItem
{
    const CLASS_NAME = 'ot_shipping';
    const TITLE = 'Flat Rate:';
}
