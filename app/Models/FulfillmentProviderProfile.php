<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * Class FulfillmentProviderProfile
 * @package App\Models
 *
 * @property int $id
 * @property string $alias
 * @property string $account_name
 */
class FulfillmentProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    public const CREATED_AT    = 'createdOn';
    public const UPDATED_AT    = null;
    public const PROVIDER_TYPE = 2;

    /**
     * @var string
     */
    protected $table = 'fulfillment';

    /**
     * @var string
     */
    protected $primaryKey = 'fulfillmentId';

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
        'is_active',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'account_id',
        'alias',
        'generic_id',
        'is_active',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'account_id',
        'account_name',
        'is_active',
        'fields',
        'provider_custom_fields',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'fulfillmentId',
        'account_name' => 'account.name',
        'account_id'   => 'fulfillmentAccountId',
        'generic_id'   => 'genericId',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne(FulfillmentProviderAccount::class, 'id', 'fulfillmentAccountId');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getAccountAttribute()
    {
        return $this->account()->first();
    }
}
