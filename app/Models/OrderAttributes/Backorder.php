<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class Backorder
 * @package App\Models\OrderAttributes
 */
class Backorder extends OrderAttribute
{
    public const TYPE_ID = 45;

    /**
     * As per inventory logic, we can create an initial order even though we DON'T have any inventory availability.
     * OR once the re-bill happened it is possible that we won't be able to fulfill the order because of inventory OOS
     * For this purpose we would want to have a different type of backorder:
     *  - fulfillment (when we could not fulfill the order)
     *  - re-bill (when we could not re-bill the order to next recurring products due to inventory unavailability)
     */
    public const FULFILLMENT = 'fulfillment';
    public const RE_BILL     = 're-bill';

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
