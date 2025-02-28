<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class GatewayReserveTermType
 * @package App\Models
 */
class GatewayReserveTermType extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table   = 'v_gateway_reserve_term_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    public $timestamps = false;
}
