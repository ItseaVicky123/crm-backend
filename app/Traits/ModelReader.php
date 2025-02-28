<?php

namespace App\Traits;

use App\Models\BaseModel;

/**
 * Trait ModelReader
 * @package App\Traits
 */
trait ModelReader
{
    /**
     * @param bool $useSlave
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static|static[]
     */
    public static function readOnly(bool $useSlave = true): \Illuminate\Database\Eloquent\Builder
    {
        return $useSlave ? self::on(BaseModel::SLAVE_CONNECTION) : self::query();
    }
}
