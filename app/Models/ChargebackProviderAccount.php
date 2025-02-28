<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class ChargebackProviderAccount
 * Reader for the v_chargeback_provider_accounts view, uses slave connection.
 * @package App\Models
 *
 * @property string $name
 */
class ChargebackProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 3;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_chargeback_provider_accounts';

    /**
     * @var string
     */
    protected $primaryKey = 'chargebackAccountId';

    /**
     * @var array
     */
    protected $visible = [
        'account_id',
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'account_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'account_id' => 'chargebackAccountId',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function profiles()
    {
        return $this->hasMany(ChargebackProviderProfile::class, 'chargebackAccountId', 'chargebackAccountId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function representments()
    {
        return $this->hasMany(ChargebackServiceRepresentment::class, 'provider_account_id', 'chargebackAccountId')
            ->where('provider_type_id', self::PROVIDER_TYPE);
    }
}
