<?php

namespace App\Models\Campaign;

use App\Models\GatewayProfile;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class AlternativePayment
 * @package App\Models\Campaign
 */
class AlternativePayment extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'campaign_alt_pay_jct';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'c_id',
        'alt_provider_id',
        'name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'provider',
        'payment_method',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'provider',
        'payment_method',
    ];

    /**
     * @return string
     */
    public function getPaymentMethodAttribute()
    {
        return $this->attributes['name'] ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function provider()
    {
        return $this->hasOne(GatewayProfile::class, 'gateway_id', 'alt_provider_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getProviderAttribute()
    {
        return $this->provider()->first();
    }
}
