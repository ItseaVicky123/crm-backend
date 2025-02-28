<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class OrderProductVolumeDiscountPrice extends OrderLineItem
{
    public const CLASS_NAME = 'ot_volume_discount_price';
    public const TITLE      = 'Volume Discount Rebill Price:';
    public const SORT_ORDER = '499';
}
