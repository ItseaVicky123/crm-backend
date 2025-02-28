<?php

namespace App\Traits;

use App\Exceptions\ModelImmutableException;

/**
 * Trait ModelImmutable
 * @package App\Traits
 */
trait ModelImmutable
{
    public static function boot()
    {
        parent::boot();

        static::creating(function() {
            throw new ModelImmutableException('You can not create a new ' . __CLASS__);
        });

        static::updating(function() {
            throw new ModelImmutableException('You can not update a ' . __CLASS__);
        });
    }
}
