<?php


namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class PaymentRouterRoutingAttribute
 * @package App\Models\Campaign
 */
class PaymentRouterRoutingAttribute extends Model
{
    use LimeSoftDeletes;

    const UPDATED_AT = null;
    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    protected $table = 'load_balance_configuration_routing_attribute';

    /**
     * @var string[]
     */
    protected $fillable = [
        'lbc_id',
        'gateway_id',
        'campaign_id',
        'route_action_flag',
        'attr_entity_id',
        'active',
        'deleted',
        'attribute_entity',
        'attribute_key',
        'attribute_value',
    ];

    /**
     * The campaign that owns this payment router gateway configuration.
     * @return HasOne
     */
    public function campaign(): HasOne
    {
        return $this->hasOne(Campaign::class, 'c_id', 'campaign_id');
    }
}