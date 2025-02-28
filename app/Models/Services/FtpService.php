<?php

namespace App\Models\Services;

use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Service;
use App\Models\FtpProviderAccount;

/**
 * Inventory sync account type service class.
 * Class InventorySync
 *
 * @package App\Models\Services
 */
class FtpService extends Service
{
    const PROVIDER_TYPE = 24;

    public static function boot()
    {
        parent::boot();
        static::creating(function ($profile) {
            $profile->activated_at  =  now();
        });
    }

    /**
     * @return HasOne
     */
    public function provider(): HasOne
    {
        return $this->hasOne(FtpProviderAccount::class, 'id', 'provider_id');
    }
}
