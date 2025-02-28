<?php

namespace App\Lib\Orders\OrderTotals;

use App\Models\VolumeDiscounts\VolumeDiscount;
use App\Models\VolumeDiscounts\VolumeDiscountCampaign;
use App\Models\VolumeDiscounts\VolumeDiscountQuantity;
use Illuminate\Support\Collection;

/**
 * Class VolumeDiscountCalculator
 * @package App\Lib\Orders\OrderTotals
 */
class VolumeDiscountCalculator
{
    /**
     * @var int $campaignId
     */
    protected int $campaignId;

    /**
     * @var int $volumeDiscountId
     */
    protected int $volumeDiscountId = 0;

    /**
     * @var VolumeDiscount|null $volumeDiscount
     */
    protected ?VolumeDiscount $volumeDiscount = null;

    /**
     * VolumeDiscountCalculator constructor.
     * @param int $campaignId
     * @param int $volumeDiscountId
     */
    public function __construct(int $campaignId, int $volumeDiscountId = 0)
    {
        $this->campaignId       = $campaignId;
        $this->volumeDiscountId = $volumeDiscountId;

        if ($this->volumeDiscountId) {
            $this->volumeDiscount = VolumeDiscount::find($this->volumeDiscountId);
        } else {
            $volumeDiscountCampaign = VolumeDiscountCampaign::where('campaign_id', $this->campaignId)->first();
            $this->volumeDiscount   = $volumeDiscountCampaign->volume_discount;
        }
    }

    /**
     * Calculate the column discount amount from the order total calculate product list payload.
     * @param Collection $productList
     * @return VolumeDiscountQuantity|null
     */
    public function getVolumeDiscountQuantity(Collection $productList): ?VolumeDiscountQuantity
    {
        $volumeDiscountQuantity = null;

        if ($this->volumeDiscount) {
            $totalItemCount = 0;

            foreach ($productList as $item) {
                if (isset($item['item_count'])) {
                    $totalItemCount += $item['item_count'];
                }
            }

            if ($totalItemCount > 0) {
                $volumeDiscountQuantity = $this->volumeDiscount->getQuantityByItemCount($totalItemCount);
            }
        }

        return $volumeDiscountQuantity;
    }

    /**
     * Filter collection of products based on the volume discount configurations
     * To return back eligible products only
     *
     * @param Collection $productList
     * @return Collection
     */
    public function getEligibleProductListCollection(Collection $productList): Collection {
        if ($this->volumeDiscount && $this->volumeDiscount->isExcludeNonRecurring()) {
            foreach ($productList as $key => $product) {
                if (!($product['is_recurring'] ?? false)) {
                    unset($productList[$key]);
                }
            }
        }

        if ($this->volumeDiscount && $eligibleProducts = $this->volumeDiscount->products) {
            $i = 0;
            foreach ($productList as $key => $product) {
                if (!in_array($product['id'],$eligibleProducts)) {
                    unset($productList[$key]);
                }

                $i++;
            }
        }

        return $productList;
    }
}
