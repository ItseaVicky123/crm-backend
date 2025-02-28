<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;
use App\Scopes\ActiveScope;

class AwaitingRetryDate extends OrderAttribute
{
    const TYPE_ID       = 35;
    const DEFAULT_VALUE = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveScope());
    }
}
