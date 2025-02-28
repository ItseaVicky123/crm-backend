<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class RestockingFee extends OrderLineItem
{
    const CLASS_NAME = 'ot_restocking_fee';
    const TITLE = 'Restocking Fee:';
}