<?php

namespace App\Models\SWS;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AppKeyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('app_key', CRM_APP_KEY);
    }
}