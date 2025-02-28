<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class NMIHideRecurring extends OrderAttribute
{
    const TYPE_ID = 56;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
