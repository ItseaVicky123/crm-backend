<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

class TaxTransactionNumber extends OrderAttribute
{
    const TYPE_ID = 46;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
