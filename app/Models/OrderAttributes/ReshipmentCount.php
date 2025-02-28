<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class ReshipmentCount
 *
 * @package App\Models\OrderAttributes
 */
class ReshipmentCount extends OrderAttribute
{
    public const TYPE_ID = 50;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
