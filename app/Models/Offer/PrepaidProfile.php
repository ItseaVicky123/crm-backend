<?php

namespace App\Models\Offer;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class PrepaidProfile
 * @package App\Models\Offer
 */
class PrepaidProfile extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'billing_offer_prepaid_profiles';

    /**
     * @var array
     */
    protected $visible = [
        'is_subscription',
        'is_convert_to_standard',
        'is_cancel_immediate',
        'is_refund_allowed',
        'is_initial_shipping_on_restart',
        'is_prepaid_shipping',
        'is_prepaid_notification_enabled',
        'created_at',
        'updated_at',
        'terms',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'terms',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'offer_id',
        'is_subscription',
        'is_convert_to_standard',
        'is_cancel_immediate',
        'is_refund_allowed',
        'is_initial_shipping_on_restart',
        'is_prepaid_shipping',
        'is_prepaid_notification_enabled',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function($profile) {
            $profile->terms()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function terms()
    {
        return $this->hasMany(PrepaidProfileTerm::class, 'profile_id', 'id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getTermsAttribute()
    {
        return $this->terms()->get();
    }

    /**
     * @param $cycles
     * @return bool
     */
    public function hasTermCycleOption($cycles)
    {
        $available = $this->terms
            ->pluck('cycles')
            ->toArray();

        return in_array($cycles, $available);
    }

   /**
    * @param $value
    * @return $this
    */
    protected function setIsConvertToStandard($value)
    {
       $this->attributes['is_convert_to_standard']         = $value;
       $this->attributes['is_subscription']                = $value;
       $this->attributes['is_initial_shipping_on_restart'] = !$value;

       return $this;
    }

    /**
     * Fetch the prepaid term based upon number of cycles.
     * @param int $cycles
     * @return PrepaidProfileTerm|null
     */
    public function getTermByCycles(int $cycles): ?PrepaidProfileTerm
    {
        $prepaidTerm = null;

        foreach ($this->terms as $term) {
            if ($cycles == $term->cycles) {
                $prepaidTerm = $term;
                break;
            }
        }

        return $prepaidTerm;
    }

    /**
     * Calculate the prepaid price based upon the number of cycles.
     * @param float $price
     * @param int $cycles
     * @param int $cycleDepth
     * @return Collection
     * @throws \Exception
     */
    public function calculatePrice(float $price, int $cycles, int $cycleDepth = 0): Collection
    {
        // Confirm that the cycles passed in matches one of the terms
        //
        if (!$this->hasTermCycleOption($cycles)) {
            throw new \Exception("Prepaid profile does not have a configuration for {$cycles} cycles.");
        }

        $offer = $this->offer;
        $term  = $this->getTermByCycles($cycles);

        if ($offer->isSelfRecurring()) {
            $subtotal = $price * $cycles;
        } else {
            // Custom recurring, must calculate the price by traversing the product chain
            //
            $subtotal = $this->calculateCustomRecurringSubtotal($cycles, $cycleDepth);
        }

        if ($term->isPercentDiscountType()) {
            $discount = ((int) $term->discount_value / 100) * $subtotal;
        } else {
            $discount = min((float) $term->discount_value, $subtotal);
        }

        return collect([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total'    => max(round($subtotal - $discount, 2), 0),
        ]);
    }

    /**
     * @param int $cycles
     * @param int $currentCycle
     * @return float|null
     */
    private function calculateCustomRecurringSubtotal(int $cycles, int $currentCycle): ?float
    {
        $offer               = $this->offer;
        $productCycleIds     = $offer->product_cycle_ids;
        $productCount        = count($productCycleIds);
        $totalProducts       = 0;
        $processableProducts = [];
        $prices              = [];

        if ($productCount != $cycles) {
            // If the product count differs from cycles, iterate until we have the right collection
            // of products.
            //
            while ($totalProducts != $cycles) {
                // Do this until we process an amount of products equal to the number of cycles requested.
                //
                if (!array_key_exists($currentCycle, $productCycleIds)) {
                    $currentCycle = 0;
                }

                $processableProducts[] = $productCycleIds[$currentCycle];
                $currentCycle++;
                $totalProducts++;
            }

        } else {
            $processableProducts = $productCycleIds;
        }

        if (count($processableProducts)) {
            foreach ($processableProducts as $productId) {
                $productModel = \App\Models\Product::findOrFail($productId);

                if ($productModel->is_bundle) {
                    // This will not take children because we don't pass children through to prepaid offer products
                    //
                    $prices[] = $productModel->calculatedBundleSubtotal();
                } else {
                    $prices[] = $productModel->price;
                }
            }
        }

        return array_sum($prices);
    }
}
