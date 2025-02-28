<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Traits\ModelImmutable;

/**
 * Class UserDepartmentType
 * Reader for the v_department_types view, uses slave connection.
 * @package app\Models
 */
class UserDepartmentType extends Model
{
    use ModelImmutable;

    CONST USER = 1;
    CONST API = 2;
    CONST SSO = 3;
    CONST CUSTOM = 4;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'v_department_types';

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'id',
    ];
}
