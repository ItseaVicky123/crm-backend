<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class GatewaySupportedFeatures
 * Reader for the v_gateway_supported_features view, uses slave connection.
 * @package App\Models
 */
class GatewaySupportedFeatures extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_gateway_supported_features';

    /**
     * @var string
     */
    protected $primaryKey = 'gateway_id';

    /**
     * @var array
     */
    protected $visible = [
        'gateway_id',
        'feature',
        'supported',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'gateway_id',
        'feature',
        'supported',
    ];

    /**
     * @return BelongsTo
     */
    public function gateway_account(): BelongsTo
    {
        return $this->belongsTo(GatewayAccount::class, 'ga_id');
    }
}
