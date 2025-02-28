<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Campaign\UserType as CampaignUserType;

/**
 * Class UserCampaignPermission
 * @package App\Models
 */
class UserCampaignPermission extends Model
{
    use Eloquence, LimeSoftDeletes, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    public $table = 'campaign_user_jct';

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
        'type_id',
        'created_by',
        'updated_by',
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
        'created_by' => 'created_id',
        'updated_by' => 'updated_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'type',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type',
        'created_at',
        'updated_at',
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

    public function campaign_user_type()
    {
        return $this->hasOne(CampaignUserType::class, 'user_id', 'user_id');
    }

    public function getTypeIdAttribute()
    {
        return $this->campaign_user_type()->first()->type_id;
    }
}
