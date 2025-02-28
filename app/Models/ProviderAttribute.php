<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class ProspectProviderAccount
 * Reader for the v_provider_attribute view, uses slave connection.
 * @package App\Models
 */
class ProviderAttribute extends Model
{
    use Eloquence, Mappable, ModelImmutable;

    const CREATED_AT = 'date_in';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_provider_attribute';

    /**
     * @var array
     */
    protected $visible = [
        'type_id',
        'account_id',
        'name',
        'value',
        'is_active'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'type_id'    => 'provider_type_id',
        'account_id' => 'provider_account_id',
        'name'       => 'attribute_name',
        'value'      => 'attribute_value',
        'is_active'  => 'attribute_active',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type_id',
        'account_id',
        'name',
        'value',
        'is_active',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $typeId
     * @param int                                   $accountId
     * @return mixed
     */
    public function scopeForProvider(Builder $query, int $typeId, int $accountId)
    {
        return $query->where('provider_type_id', $typeId)
            ->where('provider_account_id', $accountId);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $attributeName
     * @param                                       $attributevalue
     * @return mixed
     */
    public function scopeForAttributeValue(Builder $query, string $attributeName, $attributevalue)
    {
        return $query->where('attribute_name', $attributeName)->where('attribute_value', $attributevalue);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $typeId
     * @param int                                   $accountId
     * @param string                                $attributeName
     * @return mixed
     */
    public function scopeProviderAttributeValue(Builder $query, int $typeId, int $accountId, string $attributeName)
    {
        return $query->where('provider_type_id', $typeId)
            ->where('provider_account_id', $accountId)
            ->where('attribute_name', $attributeName);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function provider_object()
    {
        return $this->hasOne(ProviderObject::class, 'account_id', 'provider_account_id')
            ->where('provider_type_id', $this->attributes['provider_type_id']);
    }
}
