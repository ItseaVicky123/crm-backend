<?php


namespace App\Lib\ModuleHandlers\Offers;


use App\Exceptions\Offers\NoEligibleNextRecurringProductException;
use App\Lib\BillingModels\CustomCycleCalculator;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Offer\Offer;

/**
 * Class AlreadyPurchasedInitialHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class AlreadyPurchasedInitialHandler extends AbstractAlreadyPurchasedHandler
{
    /**
     * @var Offer $offer
     */
    protected Offer $offer;

    /**
     * @var int $currentProductId
     */
    protected int $currentProductId;

    /**
     * @var string $email
     */
    protected string $email;

    /**
     * @var int $cycleDepth
     */
    protected int $cycleDepth;

    /**
     * AlreadyPurchasedInitialHandler constructor.
     * @param Offer $offer
     * @param int $currentProductId
     * @param string $email
     * @param int $position
     */
    public function __construct(Offer $offer, int $currentProductId, string $email, int $position = 1)
    {
        parent::__construct(new ModuleRequest([
            'offer'            => $offer,
            'currentProductId' => $currentProductId,
            'email'            => $email,
        ]));

        $this->offer            = $this->moduleRequest->offer;
        $this->currentProductId = $this->moduleRequest->currentProductId;
        $this->email            = $this->moduleRequest->email;
        $this->cycleDepth       = $position - 1;
    }

    /**
     * Determine the eligible initial product for a series initial order.
     * @throws NoEligibleNextRecurringProductException
     */
    public function performAction(): void
    {
        if ($this->offer->is_series) {
            if ($eligibleProducts = $this->fetchEligibleProducts($this->offer, $this->email, $this->currentProductId)) {
                if (! in_array($this->currentProductId, $eligibleProducts)) {
                    // Current product is not in the list of eligible products, try to find one
                    //
                    $cycleCalculator = new CustomCycleCalculator($this->offer->product_cycle_ids, $eligibleProducts);

                    if ($nextProductAtCycle = $cycleCalculator->nextProductAtCycle($this->cycleDepth)) {
                        $this->resourceId = $nextProductAtCycle;
                    } else {
                        throw new NoEligibleNextRecurringProductException(__METHOD__, $this->offer->id, $this->currentProductId);
                    }
                } else {
                    $this->resourceId = $this->currentProductId;
                }
            } else {
                throw new NoEligibleNextRecurringProductException(__METHOD__, $this->offer->id, $this->currentProductId);
            }
        }
    }
}