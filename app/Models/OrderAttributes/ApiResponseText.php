<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class ApiResponseText extends OrderAttribute
{
    const TYPE_ID = 43;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
