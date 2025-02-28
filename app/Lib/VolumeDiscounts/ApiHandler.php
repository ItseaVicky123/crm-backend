<?php


namespace App\Lib\VolumeDiscounts;

use App\Lib\LineItems\LineItemCollection;
use App\Models\VolumeDiscounts\VolumeDiscount;
use App\Models\VolumeDiscounts\VolumeDiscountCampaign;

/**
 * Class ApiHandler
 * @package App\Lib\VolumeDiscounts
 */
class ApiHandler
{
    /**
     * @var int $campaignId
     */
    protected int $campaignId = 0;

    /**
     * @var int|null $volumeDiscountId
     */
    protected ?int $volumeDiscountId = null;

    /**
     * @var VolumeDiscount|null $volumeDiscount
     */
    protected ?VolumeDiscount $volumeDiscount = null;

    /**
     * Grab the volume discount and volume discount ID.
     * @param array $requestArray
     * @return $this
     */
    public function consumeVolumeDiscountFromArray(array $requestArray): self
    {
        if (isset($requestArray['volume_discount_id'])) {
            $this->setVolumeDiscountId((int) $requestArray['volume_discount_id']);
        }

        return $this;
    }

    /**
     * @param int $campaignId
     * @return ApiHandler
     */
    public function setCampaignId(int $campaignId): self
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * @param int $volumeDiscountId
     * @return ApiHandler
     */
    public function setVolumeDiscountId(int $volumeDiscountId): self
    {
        $this->volumeDiscountId = $volumeDiscountId;

        return $this;
    }

    /**
     * Attempt to load a volume discount profile.
     */
    public function initializeVolumeDiscount(): self
    {
        $this->volumeDiscount = null;

        // If there is a volume discount ID then use it, otherwise load it from campaign settings.
        //
        if ($this->volumeDiscountId) {
            $this->volumeDiscount = VolumeDiscount::find($this->volumeDiscountId);
        } else if ($this->campaignId) {
            if ($volumeDiscountCampaign = VolumeDiscountCampaign::where('campaign_id', $this->campaignId)->first()) {
                $this->volumeDiscount   = $volumeDiscountCampaign->volume_discount;
                if ($this->volumeDiscount) {
                    $this->volumeDiscountId = (int) $this->volumeDiscount->id;
                }
            }
        }

        return $this;
    }

    /**
     * @return VolumeDiscount|null
     */
    public function getVolumeDiscount(): ?VolumeDiscount
    {
        if ($this->volumeDiscount && $this->volumeDiscount->is_active) {
            return $this->volumeDiscount;
        }

        return null;
    }

    /**
     * @param \App\Lib\LineItems\LineItemCollection $lineItemCollection
     * @return \App\Lib\LineItems\LineItemCollection
     */
    public function getEligibleLineItemCollection(LineItemCollection $lineItemCollection): LineItemCollection
    {
        $volumeDiscount = $this->getVolumeDiscount();
        if ($volumeDiscount) {
            $excludeNonRecurring = $volumeDiscount->isExcludeNonRecurring();
            foreach ($lineItemCollection as $key => $lineItem) {
                if (
                    ($excludeNonRecurring && (!$lineItem->getNextRecurringProduct() || $lineItem->shouldStopRecurring())) ||
                    $lineItem->isTrial() ||
                    $lineItem->isCustomPrice()
                ) {
                    $lineItemCollection->forget($key);
                }
            }
        }

        if ($volumeDiscount && $products = $volumeDiscount->products) {
            foreach ($lineItemCollection as $key => $lineItem) {
                if (!in_array($lineItem->getBillableProductId(),$products)) {
                    $lineItemCollection->forget($key);
                }
            }
        } else {
            $lineItemCollection->forgetAll();
        }
        return $lineItemCollection;
    }

    /**
     * @param \App\Lib\LineItems\LineItemCollection $lineItemCollection
     * @return \App\Lib\LineItems\LineItemCollection
     */
    public function getNextSubscriptionEligibleLineItemCollection(LineItemCollection $lineItemCollection): LineItemCollection {
        $volumeDiscount = $this->getVolumeDiscount();
        if ($volumeDiscount) {
            if ($mainNextRecurringItem = $lineItemCollection->findMain()) {
                $mainNextRecurringDate = $mainNextRecurringItem->getNextRecurringDate();
            } else {
                $mainNextRecurringDate = '0000-00-00';
            }

            // this is case when we have main item is not recurring
            //
            if ($mainNextRecurringDate && $mainNextRecurringDate === '0000-00-00') {
                foreach ($lineItemCollection->findUpsells() as $upsell) {
                    if ($upsell->getNextRecurringProduct() && !$upsell->shouldStopRecurring() && ($nextRecurringDate = $upsell->getNextRecurringDate()) && $nextRecurringDate !== '0000-00-00') {
                        $mainNextRecurringDate = $nextRecurringDate;
                    }
                }
            }

            foreach ($lineItemCollection as $key => $lineItem) {
                if (
                    !$lineItem->getNextRecurringProduct() ||
                    $lineItem->shouldStopRecurring() ||
                    $mainNextRecurringDate !== $lineItem->getNextRecurringDate() ||
                    $lineItem->isPreservePrice() ||
                    $lineItem->isTrial()
                ) {
                    $lineItemCollection->forget($key);
                }
            }

        }

        if ($volumeDiscount && $products = $volumeDiscount->products) {
            foreach ($lineItemCollection as $key => $lineItem) {
                if (
                    ($nextRecurringProductId = $lineItem->getNextRecurringProduct()) &&
                    ! in_array($nextRecurringProductId,$products)
                ) {
                    $lineItemCollection->forget($key);
                }
            }
        } else {
            $lineItemCollection->forgetAll();
        }

        return $lineItemCollection;
    }

}
