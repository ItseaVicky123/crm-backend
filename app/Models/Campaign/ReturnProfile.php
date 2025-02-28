<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\ReturnProfile as ReturnProfileProfile;

/**
 * Class ReturnProfile
 * @package App\Models\Campaign
 */
class ReturnProfile extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    /**
     * @var string
     */
    protected $table = 'returns_campaign_jct';

    /**
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'campaign_id',
        'is_active',
        'is_deleted',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'  => 'active',
        'is_deleted' => 'deleted',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'is_active',
        'is_deleted',
        'return_profile',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_active',
        'is_deleted',
        'return_profile',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function return_profile()
    {
        return $this->hasOne(ReturnProfileProfile::class, 'id', 'profile_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getReturnProfileAttribute()
    {
        return $this->return_profile()->first();
    }
}
