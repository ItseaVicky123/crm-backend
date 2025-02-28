<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollectionSubscriptionTrack extends Model
{
    const UPDATED_AT = null;

    /**
     * @var array
     */
    protected $fillable = [
        'collection_subscription_id',
        'product_id',
        'is_skipped',
    ];

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne
     */
    public function collectionSubscription(): HasOne
    {
        return $this->hasOne(CollectionSubscription::class);
    }
}
