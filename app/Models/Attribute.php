<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use App\Lib\Lime\LimeSoftDeletes;
use App\Traits\ModelImmutable;

/**
 * Class Attribute
 * Reader for the v_attribute view, uses slave connection.
 * @package App\Models
 */
class Attribute extends Model
{

    use Eloquence, LimeSoftDeletes, ModelImmutable;

    const UPDATED_AT = false;
    const CREATED_AT = false;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_attribute';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'type_id',
        'value',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $attribute
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName(Builder $query, $attribute)
    {
        return $query->where('name', $attribute);
    }
}
