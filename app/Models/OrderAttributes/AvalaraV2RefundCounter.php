<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class AvalaraV2RefundCounter extends OrderAttribute
{
    const TYPE_ID = 55;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}

