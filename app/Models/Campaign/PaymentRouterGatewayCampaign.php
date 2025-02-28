<?php


namespace App\Models\Campaign;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sofa\Eloquence\Eloquence;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class PaymentRouterGatewayCampaign
 * @package App\Models\Campaign
 */
class PaymentRouterGatewayCampaign extends BaseModel
{
    use LimeSoftDeletes;
    use HasCreator;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';
    const UPDATED_BY = 'update_id';
    const CREATED_BY = 'created_id';

    /**
     * @var string
     */
    protected $table = 'load_balance_configuration_gateway_campaigns';

    /**
     * @var string[]
     */
    protected $fillable = [
        'lbc_id',
        'gateway_id',
        'campaign_id',
        'all_payment_types',
        'all_products',
        'no_chargeback',
        'preserve_gateway',
        'charges_today',
        'charges_month',
        'pre_auth_amount',
        'default_gateway',
        'active',
        'deleted',
    ];


    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('active', 1);
        });

        static::deleting(function ($model) {
            $model->active  = 0;
        });
    }

    /**
     * The campaign that owns this payment router gateway configuration.
     * @return HasOne
     */
    public function campaign(): HasOne
    {
        return $this->hasOne(Campaign::class, 'c_id', 'campaign_id');
    }
}
