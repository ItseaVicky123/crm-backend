<?php

namespace App\Models\Offer;

use App\Models\BillingModel\BillingModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLink extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'offer_id',
        'linked_offer_id',
        'billing_model_id',
        'rebill_depth',
        'is_enable_postcard',
        'provider_type_id',
        'profile_id',
        'announce_days_in_advance', // announce days prior to billing
        'announce_day_of_week', // day of the week when we send all scheduled announcements
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_enable_postcard' => 'boolean',
    ];

   /**
    * Boot functions - what to set when an instance is created.
    * Hook into instance actions
    */
    public static function boot()
    {
       parent::boot();
       static::creating(function ($instance) {
          $instance->created_by = get_current_user_id();
       });
       static::updating(function ($instance) {
          $instance->updated_by = get_current_user_id();
       });
    }

    /**
     * @return BelongsTo
     */
    public function parentOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo
     */
    public function childOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'linked_offer_id');
    }

    /**
     * @return BelongsTo
     */
    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class);
    }

    /**
     * Returns the soonest available recurring day for child subscription to be scheduled
     * To make sure that we're observing the announcement period and the day of the next scheduled announcement
     *
     * @return string
     */
    public function getNextEligibleRecurringDate(): string
    {
        return now()
            // Start from the next announcement date
            // If this is today, go to the next week because the announcement file was most likely already generated
            ->next($this->announce_day_of_week)
            // Now let's add the amount of days the announcement has to be made prior to billing
            ->addDays($this->announce_days_in_advance)
            // And this will be the soonest recurring date observing the announcement configurations
            ->format('Y-m-d');
    }
}
