<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class RecordedShippingAmount
 * @package App\Models\OrderAttributes
 */
class RecordedShippingAmount extends OrderAttribute
{
    public const TYPE_ID = 22;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
