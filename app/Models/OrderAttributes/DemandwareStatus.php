<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class DemandwareStatus
 * @package App\Models\OrderAttributes
 */
class DemandwareStatus extends OrderAttribute
{
    const TYPE_ID = 25;
    const STATUS_PENDING = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_ERROR = 3;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
