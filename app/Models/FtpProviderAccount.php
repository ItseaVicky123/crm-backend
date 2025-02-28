<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Services\FtpService;

/**
 * Class InventorySyncProviderAccount
 *
 * @package App\Models
 */
class FtpProviderAccount extends ProviderAccount
{
    const PROVIDER_TYPE = 24;

    /**
     * @var string
     */
    protected $table = 'v_ftp_providers';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * Fetch the inventory profiles associated with this inventory account.
     *
     * @return HasMany
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(FtpService::class, 'provider_id');
    }
}
