<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class CampaignProvider
 * @package App\Models
 */
class CampaignProvider extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'campaign_provider';

    /**
     * @var bool $timestamps
     */
    public $timestamps = false;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'campaign_id',
        'profile_id',
        'profile_generic_id',
        'account_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'campaign_id',
        'profile_id',
        'profile_generic_id',
        'account_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'type_id'    => 'provider_type_id',
        'is_active'  => 'active',
    ];
}
