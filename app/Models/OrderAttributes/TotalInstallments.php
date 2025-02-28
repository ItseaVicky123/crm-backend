<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class TotalInstallments
 * @package App\Models\OrderAttributes
 */
class TotalInstallments extends OrderAttribute
{
    const TYPE_ID = 25;
    const IS_IMMUTABLE = true;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
