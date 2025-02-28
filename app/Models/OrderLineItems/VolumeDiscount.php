<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

/**
 * Class VolumeDiscount
 * @package App\Models\OrderLineItems
 */
class VolumeDiscount extends OrderLineItem
{
    const CLASS_NAME = 'ot_volume_discount';
    const TITLE = 'Volume Discount:';
    const SORT_ORDER = '299';
}
