<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class DataVerificationProviderAccount
 * Reader for the v_data_verification_provider_accounts view, uses slave connection.
 * @package App\Models
 *
 * @property string $name
 */
class DataVerificationProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 15;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_data_verification_provider_accounts';

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
        return $this->hasMany(DataVerificationProviderProfile::class, 'account_id');
    }
}
