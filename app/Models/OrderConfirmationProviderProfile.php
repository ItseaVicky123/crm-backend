<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class OrderConfirmationProviderProfile
 * @package App\Models
 *
 * @property int $id
 * @property string $alias
 * @property OrderConfirmationProviderAccount $account
 */
class OrderConfirmationProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT    = 'createdOn';
    const UPDATED_AT    = null;
    const PROVIDER_TYPE = 7;

    /**
     * @var string
     */
    protected $table = 'callconfirm_provider';

    /**
     * @var string
     */
    protected $primaryKey = 'callconfirmProviderId';

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
        'id'           => 'callconfirmProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'callconfirmAccountId',
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
        return $this->belongsTo(OrderConfirmationProviderAccount::class, 'callconfirmAccountId');
    }
}
