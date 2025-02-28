<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

/**
 * Class VolumeDiscount
 * @package App\Models\OrderLineItems
 */
class VolumeDiscountRebill extends OrderLineItem
{
    const CLASS_NAME = 'ot_volume_discount_rebill';
    const TITLE = 'Volume Discount Rebill:';
    const SORT_ORDER = '399';
}
