<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Builder;

class StoreOffer extends Offer
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_store', function (Builder $builder) {
            $builder->where('is_store', 1);
        });

        static::creating(function ($offer) {
            parent::creating($offer);

            $offer->is_store                  = 1;
            $offer->is_immutable              = 1;
            $offer->cycle_type_id             = CycleType::TYPE_SELF;
            $offer->terminating_cycle_type_id = TerminatingCycleType::TYPE_COMPLETE;
        });
   }
}