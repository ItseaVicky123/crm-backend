<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class FraudProviderAccount
 * Reader for the v_fraudprovider_accounts view, uses slave connection.
 * @package App\Models
 */
class FraudProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 5;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_fraudprovider_accounts';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function profiles()
    {
        return $this->hasMany(FraudProviderProfile::class, 'fraudProviderAccountId');
    }
}
