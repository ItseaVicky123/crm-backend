<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class AccountUpdaterProviderAccount
 * Reader for the v_account_updater_provider_accounts view, uses slave connection.
 * @package App\Models
 */
class AccountUpdaterProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 13;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_account_updater_provider_accounts';

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
        return $this->hasMany(AccountUpdaterProviderProfile::class, 'account_id');
    }
}
