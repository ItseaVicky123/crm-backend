<?php


namespace App\Lib\ModuleHandlers\Offers;

use App\Exceptions\Offers\NoEligibleNextRecurringProductException;
use App\Lib\BillingModels\CustomCycleCalculator;
use App\Models\Offer\Offer;

class AlreadyPurchasedInitialChildHandler extends AlreadyPurchasedInitialHandler
{
    /**
     * AlreadyPurchasedInitialChildHandler constructor.
     * @param Offer $offer
     * @param int $currentProductId
     * @param string $email
     * @param int $position
     */
    public function __construct(Offer $offer, int $currentProductId, string $email, int $position = 1)
    {
        parent::__construct($offer, $currentProductId, $email, $position);
    }

    /**
     * Determine the initial child recurring product based upon series rules.
     */
    public function performAction(): void
    {
        if ($this->offer->is_series) {
            $this->resourceId = 0;

            if ($eligibleProducts = $this->fetchEligibleProducts($this->offer, $this->email)) {
                // Remove the current product because it was already decided for the initial
                //
                if (in_array($this->currentProductId, $eligibleProducts)) {
                    $eligibleProducts = array_diff($eligibleProducts, [$this->currentProductId]);
                }

                if ($eligibleProducts) {
                    $cycleCalculator = new CustomCycleCalculator($this->offer->product_cycle_ids, $eligibleProducts);

                    if ($nextProductAtCycle = $cycleCalculator->nextProductAtCycle($this->cycleDepth)) {
                        $this->resourceId = $nextProductAtCycle;
                    }
                }
            }
        }
    }
}