<?php

namespace App\Models\EntityAttributes\UpsellAttributes;

use App\Models\EntityAttributes\UpsellAttributes;

class AwaitingRetryDate extends UpsellAttributes
{
    const ATTRIBUTE_NAME = 'awaiting_retry_date';
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'attr_name' => self::ATTRIBUTE_NAME,
    ];
}
