<?php

namespace App\Models;

use App\Models\Campaign\Provider;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class AccountUpdaterProviderProfile
 * @package App\Models
 *
 * @method static Builder whereIn(string $column, array $values, string $boolean = 'and', bool $not = false)
 */
class AccountUpdaterProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT    = 'createdOn';
    const UPDATED_AT    = null;
    const PROVIDER_TYPE = 13;

    /**
     * @var string
     */
    protected $table = 'account_updater_provider';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
        'account_name',
        'fields',
        'provider_custom_fields',
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
        'account_name',
        'fields',
        'provider_custom_fields',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'account_name' => 'account.name',
        'generic_id'   => 'genericId',
        'campaign_id'  => 'campaignId',
        'is_active'    => 'active',
        'created_at'   => self::CREATED_AT,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($profile) {
            $profile->setGenericId();
        });

        static::deleting(function ($profile) {
            $profile->fields()->delete();
            $profile->provider_custom_fields()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(AccountUpdaterProviderAccount::class);
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
