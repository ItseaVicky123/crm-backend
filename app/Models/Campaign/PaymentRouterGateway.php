<?php

namespace App\Models\Campaign;

use App\Models\BaseModel;
use App\Models\PaymentRouter;
use App\Models\GatewayProfile;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PaymentRouterGateway
 * @package App\Models\Campaign
 *
 * @property GatewayProfile|null $gateway
 *
 * @method static Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class PaymentRouterGateway extends BaseModel
{
    use LimeSoftDeletes;
    use HasCreator;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';
    const UPDATED_BY = 'update_id';
    const CREATED_BY = 'created_id';

    public $maxPerPage = 100;

    /**
     * @var string
     */
    protected $table = 'load_balance_configuration_gateways';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'lbc_id',
        'gateway_id',
        'monthly_cap',
        'charges_month',
        'current_weight',
        'preserve_gateway',
        'active',
        'initial_order_limit',
        'initial_order_count',
        'declines_count',
        'rebill_order_limit',
        'rebill_order_count',
        'is_reserve',
        'gateway'
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'lbc_id',
        'gateway_id',
        'monthly_cap',
        'charges_month',
        'charges_month_resetable',
        'current_weight',
        'preserve_gateway',
        'initial_order_limit',
        'initial_order_count',
        'declines_count',
        'rebill_order_limit',
        'rebill_order_count',
        'is_reserve',
        'priority',
        'attempt',
        'active',
        'deleted',
    ];

    /**
     * @return HasOne
     */
    public function gateway(): HasOne
    {
        return $this->hasOne(GatewayProfile::class, 'gateway_id', 'gateway_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function paymentRouter()
    {
        return $this->belongsTo(PaymentRouter::class, 'lbc_id', 'id');
    }
}
