<?php

namespace App\Lib\ModuleHandlers\Offers;

use App\Exceptions\Offers\NoEligibleNextRecurringProductException;
use App\Lib\BillingModels\CustomCycleCalculator;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Offer\SubscriptionSeriesProduct;
use App\Models\Order;
use App\Models\Upsell;

/**
 * Class AlreadyPurchasedChildRecurringHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class AlreadyPurchasedChildRecurringHandler extends AbstractAlreadyPurchasedHandler
{
    /**
     * @var int $orderId
     */
    protected int $orderId;

    /**
     * @var int $orderTypeId
     */
    protected int $orderTypeId;

    /**
     * @var bool $isMain
     */
    protected bool $isMain = false;

    /**
     * @var int $currentRecurringProduct
     */
    protected int $currentRecurringProduct = 0;

    /**
     * @var int $childRecurringProduct
     */
    protected int $childRecurringProduct = 0;

    /**
     * AlreadyPurchasedChildRecurringHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);

        $this->orderId                 = $moduleRequest->get('orderId');
        $this->orderTypeId             = $moduleRequest->get('orderTypeId');
        $this->isMain                  = $this->orderTypeId == ORDER_TYPE_MAIN;
        $this->currentRecurringProduct = $moduleRequest->get('currentRecurringProduct');
        $this->childRecurringProduct   = $moduleRequest->get('childRecurringProduct');
    }

    /**
     * @throws NoEligibleNextRecurringProductException
     */
    public function performAction(): void
    {
        if ($this->orderId && $this->orderTypeId) {
            $subscriptionSeriesProduct = SubscriptionSeriesProduct::where([
                ['order_id', $this->orderId],
                ['order_type_id', $this->orderTypeId],
            ])->first();

            if ($subscriptionSeriesProduct) {
                if ($orderModel = $subscriptionSeriesProduct->order()) {
                    $this->resourceId = $this->calculateChildRecurringProduct($orderModel);
                }
            }
        }
    }

    /**
     * Calculate the child order's next recurring product when using a series offer.
     * @param Order|Upsell $orderModel
     * @throws NoEligibleNextRecurringProductException
     * @return int
     */
    private function calculateChildRecurringProduct($orderModel): int
    {
        if (($billingModelOrder = $orderModel->subscription_order)) {
            if ($targetOrderOffer = $billingModelOrder->offer) {
                if ($targetOrderOffer->is_series && $this->currentRecurringProduct && $this->childRecurringProduct) {
                    $childRecurringProduct = 0;

                    // This object is working with the current recurring product which is already happening so we can exclude it.
                    // The child recurring product is what is calculated to bill next when the child recurs.
                    // Determine if the child recurring product is eligible and if it is not, try to find one.
                    // If one isn't found, then we will throw the exception so the calling scope can decide what to do.
                    //
                    if ($eligibleProducts = $this->fetchEligibleProducts($targetOrderOffer, $orderModel->email, $this->childRecurringProduct)) {
                        // Filter the current recurring product out if it is in the eligible products list
                        //
                        if (in_array($this->currentRecurringProduct, $eligibleProducts)) {
                            $eligibleProducts = array_diff($eligibleProducts, [$this->currentRecurringProduct]);
                        }

                        if ($eligibleProducts) {
                            if (! in_array($this->childRecurringProduct, $eligibleProducts)) {
                                // The child recurring product is NOT in the eligible products list, attempt to
                                // find one.
                                $cycleDepth = $billingModelOrder->cycle_depth;

                                // Make sure we aren't in negative depths (Trial or initial)
                                // if so then let the calculated next product be what was already set.
                                //
                                if ($cycleDepth >= 0) {
                                    $cycleCalculator = new CustomCycleCalculator($targetOrderOffer->product_cycle_ids, $eligibleProducts);

                                    if ($nextProductAtCycle = $cycleCalculator->nextProductAtCycle($cycleDepth)) {
                                        $childRecurringProduct = $nextProductAtCycle;
                                    }
                                }
                            } else {
                                // The child recurring product is in the list of eligible products, so use it.
                                //
                                $childRecurringProduct = $this->childRecurringProduct;
                            }
                        }
                    }

                    if ($childRecurringProduct) {
                        return $childRecurringProduct;
                    }

                    throw new NoEligibleNextRecurringProductException(__METHOD__, $targetOrderOffer->id, $this->currentRecurringProduct);
                }
            }
        }
    }
}
