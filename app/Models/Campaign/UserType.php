<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class UserType
 * @package App\Models\Campaign
 */
class UserType extends Model
{
    use Eloquence, LimeSoftDeletes, Mappable;

    const TYPE_ALLOW = 2;
    const TYPE_RESTRICT = 1;
    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    protected $table = 'campaign_user_type';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_deleted' => 'deleted',
        'is_active'  => 'active',
        // Dates
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
        // Users
        'created_by' => 'created_id',
        'updated_by' => 'updated_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
        'updated_at',
    ];
}
