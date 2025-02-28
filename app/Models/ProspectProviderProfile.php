<?php

namespace App\Models;

use App\Models\Campaign\Provider;
use App\Scopes\ActiveScope;
use App\Traits\IsProviderProfile;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * Class ProspectProviderProfile
 * @package App\Models
 *
 * @method static Builder whereIn(string $column, array $values, string $boolean = 'and', bool $not = false)
 */
class ProspectProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable, IsProviderProfile;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;
    const PROVIDER_TYPE = 8;

    /**
     * @var string
     */
    protected $table = 'prospect_provider';

    /**
     * @var string
     */
    protected $primaryKey = 'prospectProviderId';

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
        'id',
        'account_id',
        'account_name',
        'fields',
        'provider_custom_fields',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'prospectProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'prospectAccountId',
        'generic_id'   => 'genericId',
        'campaign_id'  => 'campaign_id',
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
        return $this->belongsTo(ProspectProviderAccount::class, 'prospectAccountId');
    }
}
