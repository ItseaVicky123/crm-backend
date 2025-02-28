<?php

namespace App\Models;

use App\Models\Campaign\Provider;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class FraudProviderProfile
 * @package App\Models
 *
 * @method static Builder whereIn(string $column, array $values, string $boolean = 'and', bool $not = false)
 */
class FraudProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;
    const PROVIDER_TYPE = 5;

    /**
     * @var string
     */
    protected $table = 'fraudprovider';

    /**
     * @var string
     */
    protected $primaryKey = 'fraudProviderId';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
        'account_name',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'account_id',
        'alias',
        'generic_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'account_id',
        'account_name',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'fraudProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'fraudProviderAccountId',
        'generic_id'   => 'genericId',
        'campaign_id'  => 'campaignId',
        'is_active'    => 'active',
        'is_managed'   => 'managed_flag',
        'created_at'   => self::CREATED_AT,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($profile) {
            $profile->setGenericId();
        });

        static::deleting(function ($profile){
            $profile->fields()->delete();
            $profile->provider_custom_fields()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(FraudProviderAccount::class, 'account_id');
    }

    /**
     * @param array $campaigns
     * @return $this
     */
    public function attachCampaigns($campaigns = [])
    {
        if (count($campaigns)) {
            $base = [
                'profile_id'         => $this->id,
                'account_id'         => $this->account_id,
                'provider_type_id'   => self::PROVIDER_TYPE,
                'profile_generic_id' => $this->generic_id,
            ];

            foreach ($campaigns as $campaign) {
                $create = array_merge($base, [
                    'campaign_id' => $campaign,
                ]);

                Provider::firstOrCreate($create, $create);
            }
        }

        return $this;
    }
}
