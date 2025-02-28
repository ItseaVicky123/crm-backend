<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class SplitShipment extends OrderAttribute
{
    const TYPE_ID       = 33;
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
