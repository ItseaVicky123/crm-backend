<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class ApiResponseCode extends OrderAttribute
{
    const TYPE_ID = 42;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
