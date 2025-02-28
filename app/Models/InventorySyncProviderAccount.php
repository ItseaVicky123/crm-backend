<?php


namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Services\InventorySync;


/**
 * Class InventorySyncProviderAccount
 * Reader for the v_inventory_providers view, uses slave connection.
 * @package App\Models
 */
class InventorySyncProviderAccount extends ProviderAccount
{
    use ModelImmutable;

    const PROVIDER_TYPE = 23;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_inventory_providers';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * Fetch the inventory profiles associated with this inventory account.
     * @return HasMany
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(InventorySync::class, 'provider_id');
    }
}
