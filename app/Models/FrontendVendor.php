<?php

namespace App\Models;

class FrontendVendor extends OrderAttribute
{
    const TYPE_ID = 17;

    /**
     * @var array
     */

    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'value'
    ];
}
