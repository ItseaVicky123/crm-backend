<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Offer\Type as OfferType;
use App\Models\Order\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionSubscription extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'subscription_id',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)
            ->where('offer_type_id', OfferType::TYPE_COLLECTION);
    }

    public function purchasedProducts(): HasMany
    {
        return $this->hasMany(CollectionSubscriptionTrack::class);
    }
}
