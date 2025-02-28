<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class MembershipProviderProfile
 * @package App\Models
 *
 * @property int $id
 * @property string $alias
 * @property MembershipProviderAccount $account
 */
class MembershipProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT    = 'createdOn';
    const UPDATED_AT    = null;
    const PROVIDER_TYPE = 12;

    /**
     * @var string
     */
    protected $table = 'membership_provider';

    /**
     * @var string
     */
    protected $primaryKey = 'membershipProviderId';

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
        'id'           => 'membershipProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'membershipAccountId',
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
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MembershipProviderAccount::class, 'membershipAccountId');
    }
}
