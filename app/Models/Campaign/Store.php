<?php

namespace App\Models\Campaign;

use App\Models\Offer\BillingModel;
use App\Models\Offer\StoreOffer;
use Illuminate\Database\Eloquent\Builder;

class Store extends Campaign
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_store', function (Builder $builder) {
            $builder->where('is_store', 1);
        });

        static::creating(function ($store) {
            parent::creating($store);

            $store->is_store = 1;
        });

        static::created(function ($store) {
            // Create the StoreOffer, if it wasn't previously created
            if (!($offer = StoreOffer::first())) {
                $offer = StoreOffer::create(['name' => 'Store']);
                $offer->billing_models()->create(['id' => BillingModel::DEFAULT_ID]);
            }

            $store->offers()->attach($offer->id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function offers()
    {
        return $this->belongsToMany(
            StoreOffer::class,
            'billing_campaign_offer',
            'campaign_id',
            'offer_id',
            'c_id',
            'id'
        );
    }
}