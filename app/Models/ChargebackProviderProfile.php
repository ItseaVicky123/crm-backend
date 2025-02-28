<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * Class ChargebackProviderProfile
 * @package App\Models
 *
 * @property int $id
 * @property string $alias
 * @property ChargebackProviderAccount $account
 */
class ChargebackProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;
    const PROVIDER_TYPE = 3;

    /**
     * @var string
     */
    protected $table = 'chargeback_provider';

    /**
     * @var string
     */
    protected $primaryKey = 'chargebackProviderId';

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
        'id'           => 'chargebackProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'chargebackAccountId',
        'generic_id'   => 'genericId',
        'campaign_id'  => 'campaignId',
        'created_at'   => self::CREATED_AT,
        'is_active'    => 'active',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'account',
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
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChargebackProviderAccount::class, 'account_id');
    }
}
