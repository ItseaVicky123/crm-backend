<?php

namespace App\Models;

use App\Traits\ModelImmutable;

/**
 * Class CheckingProviderAccount
 * Reader for the v_check_provider_accounts view, uses slave connection.
 * @package App\Models
 */
class CheckingProviderAccount extends ProviderAccount
{
    use ModelImmutable;

    const PROVIDER_TYPE = 10;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    protected $table = 'v_check_provider_accounts';

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
        return $this->hasMany(CheckingProfile::class, 'checkAccountId');
    }
}