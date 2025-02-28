<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class GracePeriodCounter extends OrderAttribute
{
    const TYPE_ID = 44;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}

