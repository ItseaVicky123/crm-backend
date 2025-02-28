<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class InventorySyncProviderAccount
 * Reader for the v_gateway_supported_features view, uses slave connection.
 * @package App\Models
 */
class LegacyProductSubscription extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    protected $table = 'v_legacy_product_subscription';
    protected $hidden = ['product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
