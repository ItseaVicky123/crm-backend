<?php

namespace App\Models\DeclineRetry;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class DeclineRetryJourney
 * @package App\Models\DeclineRetry
 */
class DeclineRetryJourney extends Model
{
    public const RETRY_TYPE_RULE_BASED = 1;
    public const RETRY_TYPE_SMART      = 2;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'profile_id',
        'retry_type_id',
        'order_id',
        'order_type_id',
    ];

    /**
     * The attempts that belong to this decline retry journey.
     * @return HasMany
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(DeclineRetryAttempt::class, 'decline_retry_journey_id');
    }
}