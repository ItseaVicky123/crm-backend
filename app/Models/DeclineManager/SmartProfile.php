<?php
namespace App\Models\DeclineManager;

use App\Models\Campaign\Campaign;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SmartProfile
 * @package App\Models\DeclineManager
 *
 * @property float $discount_min
 * @property bool  $is_default
 * @property bool  $is_discount
 * @property bool  $is_discount_shipping
 * @method static first()
 */
class SmartProfile extends Model
{
    use SoftDeletes;

    /***
     * @var string
     */
    protected $table = 'decline_manager_profiles';

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    protected static $updateOnEvents = [
        'created',
        'updated',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($profile) {
            // Detach campaigns before deleting
            $profile->campaigns()->detach();
        });

        foreach (self::$updateOnEvents as $event) {
            static::$event(function ($profile) {
                if ($profile->is_default) {
                    $declineSalvageProfile = Profile::where('default_flag', 1)->first();

                    if ($declineSalvageProfile) {
                        $declineSalvageProfile->update(['is_default' => 0]);
                    }
                }
            });
        }
    }

    /**
     * @return HasMany
     */
    public function attempt_configurations(): HasMany
    {
        return $this->hasMany(DeclineManagerAttemptConfiguration::class, 'profile_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'campaigns_decline_manager_profiles',
            'decline_manager_profile_id',
            'campaign_id',
            'id',
            'c_id'
        );
    }

    /**
     * @param Subscription $subscription
     * @return bool
     */
    public function appliesToSubscription(Subscription $subscription) : bool
    {
        return $this->is_default || $this->campaigns()->pluck('id')->contains($subscription->campaign_id);
    }

    /**
     * @return mixed
     */
    public function getDiscountFlagAttribute()
    {
        return $this->is_discount;
    }

    /**
     * @return mixed
     */
    public function getDiscountShippingFlagAttribute()
    {
        return $this->is_discount_shipping;
    }
}
