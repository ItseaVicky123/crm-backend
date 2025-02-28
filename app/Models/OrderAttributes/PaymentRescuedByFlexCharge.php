<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class PaymentRescuedByFlexCharge
 *
 * @package App\Models\OrderAttributes
 *
 * This attribute is to mark orders that were rescued by FlexCharge that had been declined initially
 * by their original Payment Service Provider (PSP) on the campaign.
 */
class PaymentRescuedByFlexCharge extends OrderAttribute
{
    public const TYPE_ID       = 54;
    public const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
