<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

/**
 * Class StepDownDiscount
 * @package App\Models\OrderLineItems
 */
class StepDownDiscount extends OrderLineItem
{
    const CLASS_NAME = 'ot_stepdown_discount';
    const TITLE = 'Discount:';
}
