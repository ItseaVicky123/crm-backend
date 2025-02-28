<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class GatewayProcessor
 * @package App\Models
 *
 * @property string $name
 */
class GatewayProcessor extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table   = 'v_gateway_processor';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    public $timestamps = false;
}
