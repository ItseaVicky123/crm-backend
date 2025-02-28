<?php

namespace App\Models\Credits;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class Type
 * Reader for the v_credit_types view, uses slave connection.
 * @package App\Models
 */
class Type extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_credit_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $guarded = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
