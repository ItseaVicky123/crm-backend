<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class SkipFulfillmentPost
 * @package App\Models\OrderAttributes
 */
class SkipFulfillmentPost extends OrderAttribute
{
    const TYPE_ID = 15;
    const IS_IMMUTABLE = true;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
