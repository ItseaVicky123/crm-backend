<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class DelayConfirmationNotification extends OrderAttribute
{
    const TYPE_ID = 36;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
