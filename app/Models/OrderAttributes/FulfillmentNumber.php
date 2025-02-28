<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class FulfillmentNumber extends OrderAttribute
{
    const TYPE_ID = 30;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
