<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class ContinueSubscription extends OrderAttribute
{
    const TYPE_ID = 28;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
