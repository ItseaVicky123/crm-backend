<?php

namespace App\Lib\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;


/**
 * Class SmcActiveScope
 * @package App\Lib\Scopes
 */
class SmcActiveScope implements Scope
{
    /**
     * Filter out records that aren't both active and smc_active
     * @param Builder $builder
     * @param Model $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where([
            ['active', 1],
            ['smc_active', 1],
        ]);
    }
}