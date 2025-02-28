<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class OriginTypeId extends OrderAttribute
{
    const TYPE_ID       = 18;
    const TYPE_IMPORT   = 1;
    const TYPE_HISTORIC = 2;
    const TYPE_NOCOF    = 3;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
