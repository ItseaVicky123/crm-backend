<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class CollectionOffer
 *
 * @package App\Models\Offer
 */
class CollectionOffer extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'offer_id',
        'qty_per_purchase',
        'max_qty_per_purchase',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($collectionOffer) {
            $collectionOffer->products()->delete();
        });
    }

    /**
     * @return BelongsTo
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Products that belong to this collection offer.
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(CollectionOfferProduct::class)
            ->orderBy('position_id');
    }
}
