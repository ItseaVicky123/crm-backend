<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class VolumeDiscount
 * @package App\Models\OrderAttributes
 */
class VolumeDiscount extends OrderAttribute
{
    const TYPE_ID = 37;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
