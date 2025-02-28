<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

/**
 * Class ShippingStepDownDiscount
 * @package App\Models\OrderLineItems
 */
class ShippingStepDownDiscount extends OrderLineItem
{
    const CLASS_NAME = 'ot_stepdown_shipping_discount';
    const TITLE = 'Shipping Discount:';
}
