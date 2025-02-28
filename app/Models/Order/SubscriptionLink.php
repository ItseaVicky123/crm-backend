<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SubscriptionLink extends Model
{
    const UPDATED_AT = null;

    /**
     * @var array
     */
    protected $fillable = [
        'subscription_id',
        'linked_subscription_id',
    ];

    /**
     * @return BelongsTo
     */
    public function parentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo
     */
    public function childSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'linked_subscription_id');
    }
}
