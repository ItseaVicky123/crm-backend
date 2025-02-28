<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class LineItemSequence extends OrderAttribute
{
    const TYPE_ID = 47;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
