<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class IsDigital
 * @package App\Models\OrderAttributes
 */
class IsDigital extends OrderAttribute
{
    const TYPE_ID = 21;
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
