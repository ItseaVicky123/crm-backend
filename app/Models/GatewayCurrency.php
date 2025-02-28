<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class GatewayCurrency
 * @package App\Models
 */
class GatewayCurrency extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';

    /**
     * @var string
     */
    protected $table = 'gateway_currencies';

    /**
     * @var string
     */
    protected $primaryKey = 'gatewayCurrencyId';

    /**
     * @var array
     */
    protected $visible = [
        'currency',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'currency',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'gatewayCurrencyId',
        'gateway_id'   => 'gatewayId',
        'created_at'   => 'createdOn',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency()
    {
        return $this->hasOne(Currency::class, 'currencies_id', 'currencies_id');
    }

    public function getCurrencyAttribute()
    {
        return $this->currency()->first();
    }
}
