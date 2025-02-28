<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class ValueAddServiceProvider
 * @package App\Models
 */
class ValueAddServiceProvider extends Model
{
    use Mappable;
    use Eloquence;

    /**
     * @var string
     */
    protected $primaryKey = 'service_id';
    protected $table      = 'v_value_add_service_provider';

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'provider_type_id',
        'provider_account_id'
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'id' => 'service_id',
    ];
    /**
     * @var string[]
     */
    protected $appends = [
        'id',
    ];
}
