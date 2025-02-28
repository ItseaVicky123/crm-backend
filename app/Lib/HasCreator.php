<?php

namespace App\Lib;

use App\Models\User;

/**
 * Trait HasCreator
 * @package App\Lib
 */
trait HasCreator
{

    /**
     * @param array $additional
     * @return mixed
     */
    public static function withAuthors(array $additional = [])
    {
        if (is_string($additional)) {
            $additional = func_get_args();
        }

        return (new static)->newQuery()->with(array_merge(['creator', 'updator'], $additional));
    }

    /**
     * @return mixed
     */
    public function creator()
    {
        return $this->hasOne(User::class, 'admin_id', (defined('static::CREATED_BY') ? static::CREATED_BY : 'created_id'));
    }

    /**
     * @return mixed
     */
    public function updator()
    {
        return $this->hasOne(User::class, 'admin_id', (defined('static::UPDATED_BY') ? static::UPDATED_BY : 'created_id'));
    }

    /**
     * @return mixed
     */
    public function getCreatorAttribute()
    {
        return $this->creator()->first();
    }

    /**
     * @return mixed
     */
    public function getUpdatorAttribute()
    {
        return $this->updator()->first();
    }
}
