<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class VolumeDiscount
 * @package App\Models\OrderAttributes
 */
class VolumeDiscountRecurring extends OrderAttribute
{
    const TYPE_ID = 38;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
