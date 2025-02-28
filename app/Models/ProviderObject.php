<?php

namespace App\Models;

use App\Models\Payment\PaymentMethodProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class ProviderObject
 * Reader for the v_provider_object_lookup view, uses slave connection.
 * @package App\Models
 */
class ProviderObject extends Model
{
    use Eloquence, Mappable, ModelImmutable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_provider_object_lookup';

    /**
     * @var string[]
     */
    protected $maps = [
        'name'            => 'provider_name',
        'provider_id'     => 'account_id',
        'is_edit_page'    => 'edit_page_flag',
        'is_unified_api ' => 'unified_api_flag',
        'is_unique'       => 'unique_flag',
        'is_active'       => 'provider_active',
        'created_at'      => self::CREATED_AT,
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'provider_custom_fields',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function provider_type()
    {
        return $this->hasOne(ProviderType::class, 'id', 'provider_type_id');
    }

    /**
     * @return HasMany
     */
    public function payment_method_provider(): HasMany
    {
        return $this->hasMany(PaymentMethodProvider::class, ['provider_account_id', 'provider_type_id'], ['account_id', 'provider_type_id']);
    }

    /**
     * @return array|null
     */
    public function getProviderCustomFieldsAttribute()
    {
        $fields = null;

        if ($this->getAttribute('is_provider_custom_fields_enabled')) {
            $providerDefined = ProviderCustomFieldKey::where('provider_type_id', $this->provider_type_id)
                ->where('account_id', $this->getAttribute('account_id'))
                ->orderBy('sort', 'ASC')
                ->get();

            if ($providerDefined->count()) {
                $fields = [
                    'fields'       => $providerDefined->toArray(),
                    'max_count'    => null,
                    'user_defined' => 0,
                ];
            } else {
                $fields = [
                    'fields'       => null,
                    'max_count'    => $this->provider_custom_fields_max,
                    'user_defined' => 1,
                ];
            }
        }

        return $fields;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeHasServiceProvider(Builder $query)
    {
        return $query->where('service_provider_object', '!=', '');
    }
}
