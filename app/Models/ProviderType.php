<?php

namespace App\Models;

use App\Traits\ModelImmutable;

/**
 * Class ProviderType
 * Reader for the v_provider_type view, uses slave connection.
 * @package App\Models
 */
class ProviderType extends BaseModel
{
    use ModelImmutable;

    const TYPE_PAYMENT     = 1;
    const TYPE_FULFILLMENT = 2;
    const TYPE_CHARGEBACK  = 3;

    protected $connection = BaseModel::SLAVE_CONNECTION;


    public $table = 'v_provider_type';

    /**
     * @return mixed
     */
    public function provider_object()
    {
        return $this->belongsTo(ProviderObject::class, 'provider_type_id', 'id');
    }

    /**
     * @return bool
     */
    public function isFulfillment(): bool
    {
        return $this->id == self::TYPE_FULFILLMENT;
    }
}
