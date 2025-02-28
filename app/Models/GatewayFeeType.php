<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class GatewayFeeType
 * @package App\Models
 */
class GatewayFeeType extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table      = 'v_gateway_fee_type';

    /**
     * @var array
     */
    protected $visible    = [
        'api_name',
        'validation_rule',
    ];

    /**
     * @var array
     */
    protected $appends    = [
        'validation_rule',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'validation_rule' => 'numeric',
    ];

    public $timestamps    = false;

    public function getValidationRuleAttribute()
    {
        return 'numeric';
    }
}
