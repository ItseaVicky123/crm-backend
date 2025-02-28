<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class CheckingProfile
 * @package App\Models
 */
class CheckingProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;
    const PROVIDER_TYPE = 10;

    /**
     * @var string
     */
    protected $primaryKey = 'checkProviderId';

    /**
     * @var string
     */
    protected $table = 'check_provider';

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'account_id',
        'generic_id',
        'alias',
        'currency',
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
     * @var string[]
     */
    protected $appends = [
        'id',
        'account_id',
        'generic_id',
        'currency',
        'fields',
        'provider_custom_fields',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'id'          => 'checkProviderId',
        'account_id'  => 'checkAccountId',
        'generic_id'  => 'genericId',
        'campaign_id' => 'campaignId',
        'is_active'   => 'active',
        'is_archived' => 'archived_flag',
        'created_at'  => self::CREATED_AT,
        'archived_at' => 'archive_date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            $profile->setGenericId();
        });

        static::deleting(function ($profile) {
            $profile->fields()->delete();
            $profile->provider_custom_fields()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'gatewayId');
    }

    /**
     * @return mixed
     */
    public function getCurrencyAttribute()
    {
       return Currency::default()->first();
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('active', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(CheckingProviderAccount::class);
    }
}
