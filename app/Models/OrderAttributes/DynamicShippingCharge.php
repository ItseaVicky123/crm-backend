<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class DynamicShippingCharge
 * @package App\Models\OrderAttributes
 */
class DynamicShippingCharge extends OrderAttribute
{
    const TYPE_ID = 22;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
