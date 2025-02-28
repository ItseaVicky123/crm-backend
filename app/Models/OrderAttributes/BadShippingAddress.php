<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class BadShippingAddress
 * @package App\Models\OrderAttributes
 */
class BadShippingAddress extends OrderAttribute
{
    const TYPE_ID = 29;
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
