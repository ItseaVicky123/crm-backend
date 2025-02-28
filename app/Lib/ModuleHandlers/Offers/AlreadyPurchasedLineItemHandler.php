<?php

namespace App\Lib\ModuleHandlers\Offers;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\Offers\NoEligibleNextRecurringProductException;
use App\Lib\BillingModels\CustomCycleCalculator;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Offer\SubscriptionSeriesProduct;

/**
 * Class AlreadyPurchasedLineItemHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class AlreadyPurchasedLineItemHandler extends AbstractAlreadyPurchasedHandler
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
     * AlreadyPurchasedLineItemHandler constructor.
     * @param int $orderId
     * @param int $orderTypeId
     */
    public function __construct(int $orderId, int $orderTypeId)
    {
        parent::__construct(new ModuleRequest([
            'orderId'     => $orderId,
            'orderTypeId' => $orderTypeId,
        ]));
        $this->orderId     = $this->moduleRequest->orderId;
        $this->orderTypeId = $this->moduleRequest->orderTypeId;
        $this->isMain      = ($this->orderTypeId == ORDER_TYPE_MAIN);
    }

    /**
     * Throw an exception if line item will recur to a product already purchased.
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
                // Fetch the order model
                //
                if ($orderModel = $subscriptionSeriesProduct->order()) {
                    // Calculate the next recurring product from the target line item.  This could be a main or an upsell.
                    //
                    $this->processCalculatedNextRecurringProduct($orderModel);

                    // If it is main, we also need to check for upsells recurring on the same day and detect the
                    // next recurring product for those, if applicable.
                    //
                    if ($this->isMain && ($sameDayUpsells = $orderModel->same_day_upsells) && $sameDayUpsells->isNotEmpty()) {
                        foreach ($sameDayUpsells as $sameDayUpsell) {
                            $this->processCalculatedNextRecurringProduct($sameDayUpsell);
                        }
                    }
                }
            }
        }
    }

    /**
     * Calculate the next recurring product on a custom recurring series offer
     * @param Model $orderModel
     * @throws NoEligibleNextRecurringProductException
     */
    protected function processCalculatedNextRecurringProduct(Model $orderModel): void
    {
        if (($billingModelOrder = $orderModel->subscription_order)) {
            if ($targetOrderOffer = $billingModelOrder->offer) {
                if ($targetOrderOffer->is_series && $billingModelOrder->next_recurring_product) {
                    $nextRecurringProduct           = $billingModelOrder->next_recurring_product;
                    $calculatedNextRecurringProduct = $nextRecurringProduct;

                    if ($eligibleProducts = $this->fetchEligibleProducts($targetOrderOffer, $orderModel->email, $nextRecurringProduct)) {
                        // There are eligible products to choose from
                        //
                        if (! in_array($nextRecurringProduct, $eligibleProducts)) {
                            // The next recurring product is not in the list of eligible products, so
                            // find the next available one.
                            //
                            $cycleDepth = $billingModelOrder->cycle_depth;

                            // Make sure we aren't in negative depths (Trial or initial)
                            // if so then let the calculated next product be what was already set
                            //
                            if ($cycleDepth >= 0) {
                                $cycleCalculator = new CustomCycleCalculator($targetOrderOffer->product_cycle_ids, $eligibleProducts);

                                if ($nextProductAtCycle = $cycleCalculator->nextProductAtCycle($cycleDepth)) {
                                    $calculatedNextRecurringProduct = $nextProductAtCycle;
                                } else {
                                    // Edge case when there are no more eligible products in the sequence before recurring
                                    //
                                    $this->stopOrderThrowError($orderModel, $targetOrderOffer->id, $nextRecurringProduct);
                                }
                            }
                        }
                    } else {
                        // No eligible products to choose from, journey ends here.
                        //
                        $this->stopOrderThrowError($orderModel, $targetOrderOffer->id, $nextRecurringProduct);
                    }

                    if ($calculatedNextRecurringProduct && ($calculatedNextRecurringProduct != $nextRecurringProduct)) {
                        // Update the next recurring product to the eligible product and leave some order history
                        //
                        $billingModelOrder->update(['next_recurring_product' => $calculatedNextRecurringProduct]);
                        $orderModel->createHistoryNote(
                            "Updated line item next recurring product from {$nextRecurringProduct} to {$calculatedNextRecurringProduct} due to series offer eligibility rules",
                            'series-offer-next-product-update'
                        );
                    }
                }
            }
        }
    }

    /**
     * Stop recurring on an order with no more eligible products in the sequence.
     * @param Model $orderModel
     * @param int $offerId
     * @param int $nextRecurringProduct
     * @throws NoEligibleNextRecurringProductException
     */
    protected function stopOrderThrowError(Model $orderModel, int $offerId, int $nextRecurringProduct): void
    {
        $orderModel->update([
            'is_recurring' => 0,
            'is_hold'      => 1
        ]);
        $orderModel->createHistoryNote(
            "Stopped recurring on product {$nextRecurringProduct} due to series offer eligibility rules",
            'series-offer-stop-recurring'
        );

        throw new NoEligibleNextRecurringProductException(__METHOD__, $offerId, $nextRecurringProduct);
    }
}
