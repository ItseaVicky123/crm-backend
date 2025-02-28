<?php

namespace App\Traits;

use App\Exceptions\ModelImmutableException;

trait HasImmutable
{
    public function checkImmutable()
    {
        // Allow unguarded models to bypass
        if (! static::$unguarded) {
            if ($this->getAttribute($this->getImmutableProp())) {
                throw (new ModelImmutableException('Item may not be modified'))->setModel($this);
            }
        }
    }

    public function getImmutableProp()
    {
        return defined('static::IS_IMMUTABLE') ? static::IS_IMMUTABLE : 'is_immutable';
    }
}
