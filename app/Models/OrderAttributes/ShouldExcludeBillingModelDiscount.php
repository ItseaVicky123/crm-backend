<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class ShouldExcludeBillingModelDiscount
 *
 * @package App\Models\OrderAttributes
 *
 * This attribute is to mark those bad subscriptions that have custom preserved price with BM discount applied to them
 * which will be done through data-patch. We will use this attribute to determine if we should use an old style or new
 */
class ShouldExcludeBillingModelDiscount extends OrderAttribute
{
    public const TYPE_ID       = 53;
    public const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
