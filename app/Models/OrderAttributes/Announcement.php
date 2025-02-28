<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class Announcement
 * @package App\Models\OrderAttributes
 */
class Announcement extends OrderAttribute
{
    public const TYPE_ID = 48;

    public const SCHEDULED = 0;
    public const ANNOUNCED = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
