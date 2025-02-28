<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class GatewayCvvType
 * @package App\Models
 */
class GatewayCvvType extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table   = 'v_gateway_cvv_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    public $timestamps = false;
}
