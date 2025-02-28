<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WeightUnit
 * @package App\Models
 */
class WeightUnit extends Model
{
    use SoftDeletes, ModelImmutable;

    /**
     * @var string
     */
    protected $table  = 'vlkp_weight_unit';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'abbreviation',
    ];
}
