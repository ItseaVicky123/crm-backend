<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class OrderDescriptor extends OrderAttribute
{
    const TYPE_ID = 27;
    const IS_IMMUTABLE = true;
    const IGNORE_DUPLICATES = true;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
