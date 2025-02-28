<?php


namespace App\Models\Services;

use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Service;
use App\Models\InventorySyncProviderAccount;

/**
 * Inventory sync account type service class.
 * Class InventorySync
 * @package App\Models\Services
 */
class InventorySync extends Service
{
    const PROVIDER_TYPE      = 23;
    const FTP_INVENTORY_SYNC = 1;

    /**
     * @return HasOne
     */
    public function provider(): HasOne
    {
        return $this->hasOne(InventorySyncProviderAccount::class, 'id', 'provider_id');
    }
}
