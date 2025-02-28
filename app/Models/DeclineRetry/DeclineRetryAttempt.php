<?php

namespace App\Models\DeclineRetry;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BillingModel\OrderSubscription;

/**
 * Class DeclineRetryAttempt
 * @package App\Models\DeclineRetry
 */
class DeclineRetryAttempt extends Model
{
    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'order_type_id',
        'order_id',
        'decline_retry_journey_id',
        'attempt_number',
        'profile_id',
    ];

    /**
     * The decline retry journey that owns this decline retry attempt
     * @return BelongsTo
     */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(DeclineRetryJourney::class, 'decline_retry_journey_id');
    }

    /**
     * Fetch the builder for the line item in the billing_model_order table
     * @return OrderSubscription|null
     */
    public function lineItem(): ?OrderSubscription
    {
        return OrderSubscription::where([
            ['id', $this->order_id],
            ['type_id', $this->order_type_id],
        ])->first();
    }
}
