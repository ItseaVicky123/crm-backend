<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class MemberTempPassword
 * @package App\Models\OrderAttributes
 */
class MemberTempPassword extends OrderAttribute
{
    const TYPE_ID = 11;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];
}
