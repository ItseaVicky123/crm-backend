<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class ApiUserCampaignPermission
 * @package App\Models
 */
class ApiUserCampaignPermission extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    /**
     * @var string
     */
    public $table = 'campaign_api_user_jct';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $fillable = [
        'campaign_id',
        'user_id',
        'created_id',
        'updated_id',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

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
        'created_id' => 'created_by',
        'updated_id' => 'updated_by',
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $is_deleted;

    /**
     * @var boolean
     */
    protected $is_active;

    // Dates
    //
    protected $created_at;
    protected $updated_at;
}
