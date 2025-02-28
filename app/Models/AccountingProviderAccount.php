<?php

namespace App\Models;

use App\Models\Services\Accounting;
use App\Traits\ModelImmutable;

/**
 * Class AccountingProviderAccount
 * Reader for the v_accounting_providers view, uses slave connection.
 * @package App\Models
 */
class AccountingProviderAccount extends ProviderAccount
{
    use ModelImmutable;

    const PROVIDER_TYPE = 22;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    protected $table = 'v_accounting_providers';

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
        return $this->hasMany(Accounting::class, 'provider_id');
    }
}
