<?php

namespace App\Models;

use App\Traits\IsProviderProfile;
use App\Scopes\ProviderTypeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Service
 * @package App\Models
 */
class Service extends BaseModel
{
    use IsProviderProfile, SoftDeletes;

    const PROVIDER_TYPE = 0;

    /**
     * @var string
     */
    public $table = 'services';

    /**
     * @var string[]
     */
    protected $fillable = [
        'provider_id',
        'provider_type_id',
        'alias',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'alias',
        'created_at',
        'updated_at',
        'activated_at',
        'deactivated_at',
        // Relationships
        'fields',
        'provider_custom_fields',
        'provider',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'fields',
        'provider_custom_fields',
        'provider',
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ProviderTypeScope());
    }

    /**
     * @return HasMany
     */
    public function fields()
    {
        return $this->hasMany(ServiceFieldValue::class, 'service_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function provider_custom_fields()
    {
        return $this->hasMany(ProviderCustomField::class, 'account_id', 'provider_id');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->whereNotNull(['activated_at'])
            ->whereNull(['deactivated_at']);
    }

    public function afterSave()
    {
        return true;
    }
}
