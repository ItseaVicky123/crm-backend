<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class ShippingTotal extends OrderLineItem
{
    const CLASS_NAME = 'ot_shipping';
    const TITLE = 'Flat Rate:';
}