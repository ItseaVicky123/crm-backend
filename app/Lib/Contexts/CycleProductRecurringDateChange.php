<?php

namespace App\Lib\Contexts;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\BillingModel\OrderSubscription;
use App\Models\Offer\Offer;
use App\Models\Order;
use App\Lib\BillingModels\OfferCycleProductHandler;
use fileLogger AS Log;

/**
 * Handle what happens to an order when the recurring date or retry date changes.
 * Class CycleProductRecurringDateChange
 */
class CycleProductRecurringDateChange
{
    /**
     * @var Model $orderModel
     */
    protected Model $orderModel;

    /**
     * @var Collection $billingModelOrderCollection
     */
    protected Collection $billingModelOrderCollection;

    /**
     * SeasonalRecurringDateChange constructor.
     * @param Model $orderModel
     */
    public function __construct(Model $orderModel)
    {
        $this->orderModel                  = $orderModel;
        $this->billingModelOrderCollection = new Collection;

        if ($this->orderModel->subscription_order) {
            // Get the main order billing model order
            //
            $this->billingModelOrderCollection->push($this->orderModel->subscription_order);

            // If it is an upsell we will just check that individual item
            //
            if ($orderModel instanceof Order) {
                // Attempt to fetch the upsell billing model orders
                //
                if ($additionalLineItems = $this->orderModel->additional_products) {
                    foreach ($additionalLineItems as $item) {
                        $this->billingModelOrderCollection->push($item->subscription_order);
                    }
                }
            }
        }
    }

    /**
     * Update the next recurring products on an order where eligible
     * @return int
     */
    public function performAvailableOnSync(): int
    {
        $updated = 0;

        if ($this->billingModelOrderCollection->count() > 0) {
            /**
             * @var OrderSubscription $billingModelOrder
             */
            foreach ($this->billingModelOrderCollection as $billingModelOrder) {
                // Determine if offer on the line item is a seasonal offer
                // with the available on configuration
                //
                if ($offer = $billingModelOrder->offer) {
                    $offerId           = $offer->id;
                    $orderId           = $billingModelOrder->order_id;
                    $typeId            = $billingModelOrder->type_id;

                    $recurringDate = (string) $billingModelOrder->recurring_date;
                    $retryDate     = (string) $billingModelOrder->retry_date;

                    if ($retryDate != '0000-00-00') {
                        $nextRecurringDate = $retryDate;
                    } else {
                        $nextRecurringDate = $recurringDate;
                    }

                    $isValidDate = $nextRecurringDate && ($nextRecurringDate != '0000-00-00');

                    /**
                     * @var Offer $offer
                     */
                    if ($isValidDate && $offer->shouldSyncAvailableOnDates()) {
                        $logInfo     = "Order #{$orderId} Type #{$typeId} Offer #{$offerId} Next recurring date [{$nextRecurringDate}]";

                        try {
                            $offerCycleProductHandler = new OfferCycleProductHandler($offer);

                            if ($newNextRecurringProduct = $offerCycleProductHandler->findProductByAvailableOn($nextRecurringDate)) {
                                $oldNextRecurringProduct = $billingModelOrder->next_recurring_product;
                                // We found a product, update the next recurring product on the line item
                                //
                                Log::track(__METHOD__, "Found eligible next recurring product #{$newNextRecurringProduct} for {$logInfo}");
                                if ($billingModelOrder->update(['next_recurring_product' => $newNextRecurringProduct])) {
                                    $updated++;
                                    Log::track(__METHOD__, "Updated next recurring product to {$newNextRecurringProduct} for {$logInfo}");

                                    // Create history note
                                    //
                                    $orderModel = $billingModelOrder->order;
                                    $orderModel->createHistoryNote(
                                        "Updated line item next recurring product from {$oldNextRecurringProduct} to {$newNextRecurringProduct} due to available on date sync",
                                        'available-on-sync-update'
                                    );

                                } else {
                                    throw new \Exception("Unable to update next recurring product to {$newNextRecurringProduct}");
                                }
                            } else {
                                // No eligible product could be found. Not throwing an exception because this is expected sometimes
                                //
                                Log::track(__METHOD__, "Unable to find an eligible next recurring product to update to for Order #{$orderId} Type #{$typeId} Offer #{$offerId}", LOG_WARN);
                            }
                        } catch (\Exception $e) {
                            Log::track(__METHOD__, "Error caught for offer {$offerId}", LOG_ERROR);
                        }
                    } else {
                        Log::track(__METHOD__, "Order #{$orderId} Type #{$typeId} Offer #{$offerId} is not eligible for available on sync", LOG_WARN);
                    }
                }
            }
        }

        return $updated;
    }
}
