<?php

namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use App\Models\Campaign\Campaign;

/**
 * Class Subscription
 * @package App\Models\BillingModel
 */
class Subscription extends Model
{
    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'billing_subscription';

    /**
     * @var array
     */
    protected $fillable = [
        'campaign_id',
        'created_by',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription_orders()
    {
        return $this->hasMany(OrderSubscription::class, 'subscription_id');
    }
}
