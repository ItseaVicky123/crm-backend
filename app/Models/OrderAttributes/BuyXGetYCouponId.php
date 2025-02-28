<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class BuyXGetYDiscount
 * @package App\Models\OrderAttributes
 */
class BuyXGetYCouponId extends OrderAttribute
{
    public const TYPE_ID = 39;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
