<?php

namespace App\Lib\Orders;

use App\Facades\SMC;
use App\Models\Country;
use App\Models\DeclineManager\DeclineManagerPreservation;
use App\Models\DeclineManager\DeclineSalvagePreserve;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Shipping;
use App\Models\Upsell;
use App\Services\OrderHandler;
use billing_models\api\order_product_entry;
use billing_models\prepaid_discount;
use CouponHelper;
use Illuminate\Support\Collection;
use tax_provider;

/**
 * Class NextRecurringOrderPriceCalculator
 *
 * This calculator is intended to calculate next recurring order amount on either all the recurring items
 * or some specific line items that you can define by a collection of Order or Upsell models to the setLineItemsList() function
 * Another way to filter line items is by specifying recurring date of the line items that should be used to calculate
 *
 * The main function calculate() will return back the calculated total amount that will include
 * order subtotal, applied discounts, shipping amount, applied shipping discount and taxes
 *
 * Currently supported discounts:
 * - Retry Discount
 * - Billing Model
 * - Coupon
 * - Prepaid Discount
 * - Re-bill Discount
 *
 * @package App\Lib\Orders
 */
class NextRecurringOrderPriceCalculator
{
    private Order $order;
    private int   $orderId;
    private ?int  $shippingId = null;

    private ?string     $recurringDate = null;
    private ?Collection $lineItemsList = null;
    private Collection  $nextLineItems;
    private ?Shipping   $shipping      = null;

    private float $shippingAmount         = 0.0;
    private float $taxAmount              = 0.0;
    private float $salesTaxPercentage     = 0.0;
    private float $shippingDiscountAmount = 0.0;
    private float $shippingTaxPercentage  = 0.0;
    private float $shippingTaxAmount      = 0.0;
    private float $vatTaxPct              = 0.0;
    private float $vatTaxFactor           = 0.0;

    private bool $shouldCalculateTaxes         = false;
    private bool $useTheSoonestRecurringDate   = false;
    private bool $useNaturalOrderRecurringDate = false;
    private bool $shouldCalculateShipping      = false;

    /**
     * This calculator should be used to calculate a group of subscriptions
     * that belongs to the same recurring date and order.
     * To make sure that the discount calculation will be performed the same way it's done on re-bill
     */
    public function __construct($orderId, ?string $recurringDate = null)
    {
        $this->orderId = (int) $orderId;
        $this->order   = Order::readOnly()->find($this->orderId);
        // We should calculate taxes by default
        $this->setShouldCalculateTaxes(true);
        // We should calculate shipping by default
        $this->setShouldCalculateShipping(true);

        if (! empty($recurringDate)) {
            $this->setRecurringDate($recurringDate);
        }
    }

    /**
     * Because in our system one order could be a combination of different recurring day shipments
     * we should calculate next recurring information for every shipment separately,
     * to calculate the most accurately discounts, shipping and taxes
     *
     * @param $orderId
     * @return \Illuminate\Support\Collection
     */
    public static function createCalculators($orderId): Collection
    {
        $uniqueRecurringDates = Order::readOnly()
            ->customSelectWithComment(
                "GREATEST(date_purchased, recurring_date, CURDATE()) AS recurring_date",
                "Fetch all recurring dates for the main and upsell orders, considering past today recurring as for today's day"
            )
            ->where('orders_id', $orderId)
            ->whereNotNull('recurring_date')
            ->union(
                Upsell::readOnly()
                    ->selectRaw("GREATEST(date_purchased, recurring_date, CURDATE()) AS recurring_date")
                    ->where('main_orders_id', $orderId)
                    ->whereNotNull('recurring_date')
            )
            ->groupBy('recurring_date')
            ->get();

        $nextRecurringShipmentCalculators = collect();

        foreach ($uniqueRecurringDates as $recurringDate) {
            $nextRecurringShipmentCalculators->push(new self($orderId, $recurringDate->recurring_date));
        }

        return $nextRecurringShipmentCalculators;
    }

    public function setShouldCalculateTaxes(bool $shouldCalculateTaxes): self
    {
        $this->shouldCalculateTaxes = $shouldCalculateTaxes;

        return $this;
    }

    public function setShouldCalculateShipping(bool $shouldCalculateShipping): self
    {
        $this->shouldCalculateShipping = $shouldCalculateShipping;

        return $this;
    }

    public function setUseTheSoonestRecurringDate(bool $useTheSoonestRecurringDate): self
    {
        $this->useTheSoonestRecurringDate = $useTheSoonestRecurringDate;

        return $this;
    }

    public function setUseNaturalOrderRecurringDate(bool $useNaturalOrderRecurringDate): self
    {
        $this->useNaturalOrderRecurringDate = $useNaturalOrderRecurringDate;

        return $this;
    }

    /**
     * If the date in the past this function will return today's date, otherwise the original date
     *
     * @param string $recurringDate
     * @return \Carbon\Carbon
     */
    public static function determineNextRecurringDate(string $recurringDate): \Carbon\Carbon
    {
        $nextRecurringDate = \Carbon\Carbon::parse($recurringDate);

        /** If the date is in the past, we should overwrite it to today's date */
        if ($nextRecurringDate->isPast()) {
            $nextRecurringDate = now();
        }

        return $nextRecurringDate->startOfDay();
    }

    /**
     * Get Next Recurring Order Line Items if not provided
     *
     * @return void
     */
    public function populateLineItemList(): void
    {
        if (! $this->lineItemsList) {
            $this->lineItemsList = $this->order->active_recurring_items;

            if (! $this->lineItemsList->count()) {
                return;
            }

            /** The function will determine the next recurring date using natural DB record creation order */
            if ($this->useNaturalOrderRecurringDate) {
                $this->setRecurringDate(
                    $this->lineItemsList
                        ->first()
                        ->next_valid_recurring_date
                        ->format('Y-m-d')
                );
            }

            /** The function will determine the soonest recurring date and will filter out to get same date items */
            if ($this->useTheSoonestRecurringDate) {
                $this->setRecurringDate(
                    $this->lineItemsList
                        ->sortBy('next_valid_recurring_date')
                        ->first()
                        ->next_valid_recurring_date
                        ->format('Y-m-d')
                );
            }

            /** Filter recurring items by recurring date if applicable */
            if ($this->recurringDate) {
                $nextRecurringDate = self::determineNextRecurringDate($this->recurringDate);

                /**
                 * If the date is today we should consider any subscription that is prior today, same as recurring cron does
                 * Otherwise only the same date subscriptions should be considered
                 */
                $operator = $nextRecurringDate->isToday() ? '<=' : '=';

                $this->lineItemsList = $this->lineItemsList
                    ->where(
                        'next_valid_recurring_date',
                        $operator,
                        $nextRecurringDate->format('Y-m-d H:i:s')
                    );
            }
        }
    }

    /**
     * Specify the next recurring date. This will filter out all recurring items by this recurring date
     *
     * @param string $recurringDate
     * @return $this
     */
    public function setRecurringDate(string $recurringDate): self
    {
        $this->recurringDate = $recurringDate;

        return $this;
    }

    /**
     * A collection of Order|Upsell objects
     * If not used we will just get all recurring items
     *
     * @param \Illuminate\Support\Collection $lineItemsList
     * @return \App\Lib\Orders\NextRecurringOrderPriceCalculator
     */
    public function setLineItemsList(Collection $lineItemsList): self
    {
        $this->lineItemsList = $lineItemsList;

        return $this;
    }

    /**
     * Calculating all amounts for the next recurring line items
     *
     * @return float
     */
    public function calculate(): float
    {
        $this->populateLineItemList();
        $this->nextLineItems = collect();

        /** No active recurring items were found */
        if (! $this->lineItemsList->count()) {
            return 0.0;
        }

        /**
         * Sorting order line items in the same way we do on re-bill.
         * Where the first one will be main order line item if present.
         * Otherwise, the first inserted upsell line item
         */
        $this->lineItemsList = $this->lineItemsList
            ->sortBy('upsell_orders_id')
            ->values();

        /** Create a NextLineItem object for each recurring line item */
        foreach ($this->lineItemsList as $lineItem) {
            $this->nextLineItems->push(new NextLineItem($lineItem));
        }

        /** Use order subscription ID for an easy access */
        $this->nextLineItems = $this->nextLineItems->keyBy('subscriptionId');

        $this
            ->determineShippingId()
            ->calculateDiscounts()
            /**
             * Because of the shipping threshold amount, we should calculate shipping after all discounts
             * except coupon discount. Because that's where we get the shipping discount applied
             */
            ->calculateShippingAmount()
            ->calculateCouponDiscounts()
            ->calculateTaxAmount();

        return $this->getTotalAmount();
    }

    public function getNextLineItemBySubscriptionId(string $subscriptionId): ?NextLineItem
    {
        return $this->nextLineItems[$subscriptionId];
    }

    public function getShippingMethod(): ?Shipping
    {
        if (! $this->shipping && $this->shippingId) {
            $this->shipping = Shipping::readOnly()->find($this->shippingId);
        }

        return $this->shipping;
    }

    public function getShippingId(): ?int
    {
        return $this->shippingId;
    }

    /**
     * Get the order total including all the calculated amounts
     *
     * @return float
     */
    public function getTotalAmount(): float
    {
        $total = 0.0;

        /** Calculate discounted subtotal */
        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use (&$total) {
            $total += $nextLineItem->getTotal();
        });

        /** Add shipping and taxes */
        $total += $this->getShippingTotalAmount() + $this->getTaxAmount() + $this->getVatTaxAmount();

        return round($total, 2);
    }

    public function getSubtotalAmount(): float
    {
        $subtotalAmount = 0.0;

        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use (&$subtotalAmount) {
            $subtotalAmount += $nextLineItem->getSubtotal();
        });

        return $subtotalAmount;
    }

    public function getShippingAmount(): float
    {
        return $this->shippingAmount;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function getSalesTaxPercentage(): float
    {
        return $this->salesTaxPercentage;
    }

    public function getVatTaxAmount(): float
    {
        return $this->vatTaxFactor;
    }

    public function getVatTaxRate(): float
    {
        return $this->vatTaxPct;
    }

    public function getNextLineItems(): Collection
    {
        return $this->nextLineItems;
    }

    public function getMainOrder(): Order
    {
        return $this->order;
    }

    public function getRecurringDate(): string
    {
        return date('Y-m-d', strtotime($this->recurringDate));
    }

    public function getLineItemDropdown(): array
    {
        return $this->nextLineItems
            ->map(function (NextLineItem $nextLineItem) {
                return [
                    'unit_price'      => $nextLineItem->getUnitPrice(),
                    'qty'             => $nextLineItem->getQuantity(),
                    'subtotal'        => $nextLineItem->getSubtotal(),
                    'total'           => $nextLineItem->getTotal(),
                    'discount_amount' => $nextLineItem->getDiscountAmount(),
                    'discounts'       => $nextLineItem->getDiscounts(),
                    'tax_amount'      => $nextLineItem->getTaxAmount(),
                    'tax_rate'        => $nextLineItem->getTaxRate(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getDiscounts(): array
    {
        $discounts = [];

        $this->nextLineItems
            ->map(function (NextLineItem $nextLineItem) use (&$discounts) {
                foreach ($nextLineItem->getDiscounts() as $name => $amount){
                    if (! isset($discounts[$name])) {
                        $discounts[$name] = 0.0;
                    }

                    $discounts[$name] = round($discounts[$name] + $amount, 2);
                }
            });

        return $discounts;
    }

    /**
     * Shipping is excluded
     * @return float
     */
    public function getDiscountAmount(): float
    {
        $totalDiscountAmount = 0.0;

        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use (&$totalDiscountAmount) {
            $totalDiscountAmount += $nextLineItem->getDiscountAmount();
        });

        return round($totalDiscountAmount, 2);
    }

    public function getShippingDiscountAmount(): float
    {
        return $this->shippingDiscountAmount;
    }

    public function getShippingTotalAmount(): float
    {
        return $this->getShippingAmount() - $this->getShippingDiscountAmount();
    }

    public function getShippingTaxAmount(): float
    {
        return $this->shippingTaxAmount;
    }

    public function getShippingTaxPercentage(): float
    {
        return $this->shippingTaxPercentage;
    }

    /**
     * Determine next recurring shipping ID
     *
     * @return $this
     */
    private function determineShippingId(): self
    {
        // Reset shipping Info
        $this->shippingAmount = 0.0;
        $this->shippingId     = null;
        $this->shipping       = null;

        // Skip shipping calculation
        if (! $this->shouldCalculateShipping) {
            return $this;
        }

        // Check if order's line item products are shippable
        $shippable = false;
        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use (&$shippable) {

            //Use line item shipping override ID if exists
            if (!$this->shippingId) {
                $this->shippingId = $nextLineItem->getLineItem()->order_subscription->next_shipping_id ?? null;
            }

            if ($nextLineItem->getProduct()->is_shippable) {
                $shippable = true;
            }

            if($shippable && $this->shippingId) {
                return;
            }
        });

        if(!$shippable) {
            $this->shippingId = null;
            return $this;
        }

        if($this->shippingId) {
            return $this;
        }

        /**
         * Use primary line item shipping override ID if exists
         * Otherwise check if current main order already has the shipping ID set and re-use it
         */
        if ($this->shippingId = $this->order->shipping_id ?? null) {
            return $this;
        }

        /**
         * If main order shipping method name is not set or `NA`, that means that order just became shippable
         * Fetch last used shipping in a past for the main order and use it.
         *
         * Note: this is the way re-bill is handling. Check recurring_shipping_method class
         */
        if (empty($this->order->shipping_method_name) || $this->order->shipping_method_name === 'NA') {
            if ($shipMethod = OrderHandler::fetchLastUsedRecurringShipping($this->orderId)) {
                $this->shippingId = $shipMethod->id;
                $this->shipping   = $shipMethod;
            }
        }

        return $this;
    }

    /**
     * Reset and calculate all discounts accept coupon discount, since it should be after we determine shipping amount
     * and shipping threshold is calculated based on all discounts accept the coupon
     *
     * @return $this
     */
    private function calculateDiscounts(): self
    {
        return $this
            ->resetDiscounts()
            ->calculatePrePaidDiscount()
            ->calculateBillingModelDiscount()
            ->calculateReBillDiscountAmount()
            ->calculateRetryDiscountAmount();
    }

    private function resetDiscounts(): self
    {
        $this->shippingDiscountAmount = 0.0;

        $this->nextLineItems->map(fn (NextLineItem $nextLineItem) => $nextLineItem->resetDiscounts());

        return $this;
    }

    /**
     * Calculate prepaid discount if applicable
     *
     * @return $this
     */
    private function calculatePrePaidDiscount(): self
    {
        if (! \system_module_control::check(SMC::OFFER_PREPAID)) {
            return $this;
        }

        $this->nextLineItems->map(function (NextLineItem $nextLineItem) {
            $orderSub = $nextLineItem->getLineItem()->order_subscription;

            /** Proceed only if the line item has prepaid offer type */
            if ($orderSub && $orderSub->offer->typeIsPrepaid()) {
                $isLastCycle = $orderSub->prepaid_cycles === $orderSub->current_prepaid_cycle;

                /** Check if currently is the last cycle */
                if ($isLastCycle && $orderSub->cycles_remaining === 1) {
                    /** This will consider all the use cases of the prepaid discount */
                    $prepaid_discount = new prepaid_discount([
                        'product_id'    => $nextLineItem->getProductId(),
                        'product_price' => $nextLineItem->getUnitPrice() / $orderSub->prepaid_cycles,
                        'offer_id'      => $orderSub->offer_id,
                        'cycles'        => $orderSub->prepaid_cycles,
                        'cycle_depth'   => $orderSub->cycle_depth,
                        'order_id'      => $nextLineItem->getLineItem()->id,
                        'type_id'       => $nextLineItem->getLineItem()->type_id,
                        'quantity'      => $nextLineItem->getQuantity(),
                    ]);
                    $prepaidDiscount = $prepaid_discount->calculate();

                    /** Apply prepaid discount */
                    $nextLineItem->addDiscount('prepaid_discount', $prepaidDiscount);
                    /** Disable BM discount for the prepaid subscription */
                    $nextLineItem->setShouldCalculateBillingModelDiscount(false);
                }
            }
        });

        return $this;
    }

    /**
     * Calculate Retry Discount if applicable
     *
     * @return $this
     */
    private function calculateRetryDiscountAmount(): self
    {
        // Check if we have preserved Decline Salvage Discount
        $retryDiscount = DeclineSalvagePreserve::readOnly()->where('order_id', $this->orderId)->first();

        // Otherwise check if we have preserved Smart Dunning Discount
        if (! $retryDiscount) {
            $retryDiscount = DeclineManagerPreservation::readOnly()->where('order_id', $this->orderId)->first();
        }

        // Calculate retry discount if applicable
        if ($retryDiscount) {
            $retryPercent = $retryDiscount->discount_percent / 100;

            // Apply discount amount per each line item
            $this->nextLineItems->map(function (NextLineItem $nextLineItem) use ($retryPercent) {
                $nextLineItem->addDiscount(
                    'retry_discount',
                    round($nextLineItem->getTotal() * $retryPercent, 2)
                );
            });
        }

        return $this;
    }

    /**
     * Calculate Billing Model Discount if applicable
     *
     * @return $this
     */
    private function calculateBillingModelDiscount(): self
    {
        $this->nextLineItems->map(function (NextLineItem $nextLineItem) {
            $billingModelOrder = $nextLineItem->getLineItem()->order_subscription;

            /** Check if this is billing model order and if the BM calculations is enabled */
            if (! $billingModelOrder || ! $nextLineItem->shouldCalculateBillingModelDiscount()) {
                return;
            }

            $stickyDiscountPercent      = $billingModelOrder->sticky_discount_percent;
            $stickyDiscountFlatAmount   = $billingModelOrder->sticky_discount_flat_amount;
            $billingModelDiscountAmount = 0.0;

            if ($stickyDiscountFlatAmount > 0) {
                $billingModelDiscountAmount = $stickyDiscountFlatAmount;
            } else if ($stickyDiscountPercent > 0) {
                $billingModelDiscountAmount = round($nextLineItem->getUnitPrice() * ($stickyDiscountPercent / 100), 2);
            }

            /** Apply Billing Model Discount if applicable */
            if ($billingModelDiscountAmount > 0) {
                /** rounding up the result to make it the same way we do on the re-bill */
                $nextLineItem->addDiscount('billing_model', $billingModelDiscountAmount * $nextLineItem->getQuantity());
            }
        });

        return $this;
    }

    private function calculateCouponDiscounts(): self
    {
        $campaignId   = $this->order->campaign_id;
        $BXGYCouponId = $this->order->buyXGetYCouponId->value ?? null;
        $promoCode    = PromoCode::readOnly()
            ->where('id', $this->order->promo_code_id)
            ->hasByNonDependentSubquery('coupons', function ($q) use ($campaignId) {
                $q->where('is_lifetime', true)
                    ->hasByNonDependentSubquery('campaigns', fn($q) => $q->whereCId($campaignId));
            })
            ->first();

        $couponProducts = $this->nextLineItems
            ->map(function (NextLineItem $nextLineItem) {
                return [
                    'id'              => $nextLineItem->getProductId(),
                    'variant_id'      => $nextLineItem->getVariantId(),
                    'qty'             => $nextLineItem->getQuantity(),
                    'price'           => $nextLineItem->getTotal() / $nextLineItem->getQuantity(),
                    /** This is how we can map the result to a line item to apply line item coupon discount */
                    'subscription_id' => $nextLineItem->getLineItem()->subscription_id,
                ];
            })
            ->values()
            ->toArray();

        $couponHelper = CouponHelper::calculateEligibleCouponDiscounts(
            $campaignId,
            $BXGYCouponId,
            $promoCode->code ?? null,
            $couponProducts,
            $this->getShippingAmount()
        );

        if ($couponHelper) {
            $couponDiscountAmount = (float) $couponHelper->totalCouponDiscount - $couponHelper->shippingCouponAmount;

            if ($couponDiscountAmount > 0) {
                foreach ($couponProducts as $product) {
                    if (!empty($productDiscountAmount = $product['product_coupon_discount_snapshot'])) {
                        $this->getNextLineItemBySubscriptionId($product['subscription_id'])
                            ->addDiscount('coupon', $productDiscountAmount);
                    }
                }
            }

            if (! $couponHelper->isBuyXGetY) {
                $this->shippingDiscountAmount = (float) $couponHelper->shippingCouponAmount;
            }
        }

        return $this;
    }

    /**
     * Calculate Re-Bill Discount Amount if applicable
     *
     * @return $this
     */
    private function calculateReBillDiscountAmount(): self
    {
        if (! $this->order->rebill_discount) {
            return $this;
        }

        $reBillPercent = $this->order->rebill_discount / 100;

        /** Apply discount amount per each line item */
        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use ($reBillPercent) {
            $nextLineItem->addDiscount(
                'rebill_discount',
                round($nextLineItem->getTotal() * $reBillPercent, 2)
            );
        });

        return $this;
    }

    /**
     * If main product is not shippable, the entire order will not be shippable too
     * tested and confirmed that this is the way our system behaves right now on re-bill
     *
     * @return self
     */
    private function calculateShippingAmount(): self
    {
        // If shipping ID is not defined, this order is not shippable
        if (! $this->shippingId || ! $shipping = Shipping::readOnly()->where('id', $this->shippingId)->first()) {
            return $this;
        }

        /**
         * NOTE: By some odd reason the re-bill takes to consideration only the main re-bill product
         * when it comes to determining next shipping amount.
         * This is only applies to Prepaid and Trial Workflow shipping amounts
         */
        $nextLineItem   = $this->getPrimaryNextLineItem();
        $orderSub       = $nextLineItem->getLineItem()->order_subscription;
        $isThresholdMet = false;

        /** If this is Trial with Delay Billing, we should not charge shipping */
        if ($orderSub && $orderSub->cycle_depth === order_product_entry::DEPTH_TRIAL_DELAY && $orderSub->offer->delayed_billing_flag) {
            return $this;
        }

        /** By default, we should use recurring shipping amount */
        $this->shippingAmount = $shipping->amount_recurring;

        /** Use threshold amount if applicable and the retry discount was not applied */
        if ($shipping->threshold_amount && $this->getTotalAmount() >= $shipping->threshold_amount && ! $nextLineItem->getDiscount('retry_discount')) {
            $this->shippingAmount = $shipping->threshold_charge_amount;
            $isThresholdMet       = true;
        }

        if ($orderSub) {
            /** If this is prepaid with subscription type that in the last cycle, use its configurations for shipping */
            if (\system_module_control::check(SMC::OFFER_PREPAID) && $orderSub->offer->typeIsPrepaid()) {
                if ($orderSub->prepaid_cycles === $orderSub->current_prepaid_cycle) {
                    if ($orderSub->cycles_remaining === 1 && ($orderSub->offer->prepaid_profile->is_subscription ?? false)) {
                        /** "Charge initial shipping price on renewal" */
                        $useInitialShipping = $orderSub->offer->prepaid_profile->is_initial_shipping_on_restart;
                        $isShippingPrepaid  = $orderSub->offer->prepaid_profile->is_prepaid_shipping;

                        if ($isShippingPrepaid && $isThresholdMet) {
                            /** Undo the threshold amount, since we met the threshold due to prepaid subscription */
                            $this->shippingAmount = $shipping->amount_recurring;
                        }

                        /**
                         * If "Charge initial shipping price on renewal" is enabled and not prepaid,
                         * fetch the initial shipping method
                         */
                        if ($useInitialShipping && ($isShippingPrepaid || ! $isThresholdMet)) {
                            $order = Order::readOnly()->where('subscription_id', $orderSub->subscription_id)->first();

                            if ($order && $order->ship_method) {
                                $shipping = $order->ship_method;
                            } else {
                                /** If shipping was not found through the main order, check for the upsell */
                                $upsell = Upsell::readOnly()->where('subscription_id', $orderSub->subscription_id)->first();

                                if ($upsell && $upsell->ship_method) {
                                    $shipping = $upsell->ship_method;
                                }
                            }

                            /** Use initial shipping amount */
                            $this->shippingAmount = $shipping->amount_trial;
                            $this->shippingId     = $shipping->id;
                        }

                        /** If shipping is prepaid then we should multiply it by a count of prepaid cycles */
                        if ($isShippingPrepaid) {
                            $this->shippingAmount *= $orderSub->prepaid_cycles;
                        }

                        $this->shipping = $shipping;

                        return $this;
                    }
                } else if ($orderSub->offer->prepaid_profile->is_prepaid_shipping ?? false) {
                    /** If shipping is also prepaid then we should not charge it on prepaid cycles */
                    /** @TODO uncomment it out when the re-bill issue will be fixed and we will not charge it there */
                    //$this->shippingAmount = 0.0;

                    return $this;
                }
            }

            /** Don't do Trial Workflow calculation if threshold met */
            if ($isThresholdMet) {
                return $this;
            }

            /** Use trial workflow shipping amount if applicable */
            if (! is_null($trialWorkflowShippingAmount = $nextLineItem->getTrialWorkflowShippingAmount($shipping, $this->shippingAmount))) {
                $this->shippingAmount = $trialWorkflowShippingAmount;

                return $this;
            }
        }

        /** Don't do Shipping overrides if threshold met */
        if ($isThresholdMet) {
            return $this;
        }

        /** Check if any of the subscriptions have shipping override set */
        $shippingOverrideAmount = 0;
        $overridesCount         = 0;

        $this->nextLineItems->map(function (NextLineItem $nextLineItem) use (&$shippingOverrideAmount, &$overridesCount) {
            if (! is_null($shippingAmount = $nextLineItem->getShippingOverrideAmount())) {
                $shippingOverrideAmount += $shippingAmount;
                $overridesCount++;
            }
        });

        /** Use shipping overrides is applicable */
        if ($overridesCount) {
            /**
             * If there is at least one product that does NOT have the OVERRIDE
             * Add regular shipping amount for the remaining product(s), otherwise ignore it by setting initial price to $0
             */
            if ($overridesCount === $this->nextLineItems->count()) {
                $this->shippingAmount = 0.0;
            }

            $this->shippingAmount += $shippingOverrideAmount;
        }

        return $this;
    }

    /**
     * Calculate Tax Amount for the entire order
     *
     * @return $this
     */
    private function calculateTaxAmount(): self
    {
        /** Reset tax amounts */
        $this->shippingTaxAmount = 0.0;
        $this->taxAmount         = 0.0;

        /** Check if tax calculation is enabled */
        if (! $this->shouldCalculateTaxes) {
            return $this;
        }

        $taxableAmount  = 0.0;
        $shippingAmount = $this->getShippingTotalAmount();
        $taxProducts    = $this->nextLineItems
            ->map(function (NextLineItem $nextLineItem) use (&$taxableAmount) {
                $product  = $nextLineItem->getProduct();
                $category = $product->category()->first()->category;

                /** Calculate total taxable amount */
                if ($product->is_taxable) {
                    $taxableAmount += $nextLineItem->getTotal();
                }

                return [
                    'id'       => $product->id,
                    /** For the pre_sale request we should send the total */
                    'amt'      => $nextLineItem->getTotal(),
                    'qty'      => $nextLineItem->getQuantity(),
                    'sku'      => $product->sku,
                    'des'      => $product->description,
                    'tax'      => $product->is_taxable,
                    'ship'     => $product->is_shippable,
                    'cat'      => $category->name ?? '',
                    'cat_desc' => $category->description ?? '',
                    'tax_code' => $product->tax_code,
                    'subs_id'  => $nextLineItem->subscriptionId
                ];
            })
            ->values()
            ->toArray();


        /** Next recurring products are not taxable and no shipping amount, skip it */
        if (! $taxableAmount && ! $shippingAmount) {
            return $this;
        }

        $order   = $this->order;
        $address = [
            'shipZip'       => $order->delivery_postcode,
            'billZip'       => $order->billing_postcode,
            'shipCountryId' => $order->delivery_country,
            'billCountryId' => $order->billing_country,
            'shipStateId'   => $order->delivery_state_id,
            'billStateId'   => $order->billing_state_id,
            'shipAddress'   => $order->delivery_street_address,
            'shipCity'      => $order->delivery_city,
            'campaignId'    => $order->campaign_id,
        ];

        /** Use 3rd party provider to calculate taxes if applicable */
        if ($taxProviderId  = $order->campaign->tax_provider_id) {
            $tax_provider = new tax_provider($taxProviderId);
            $tax_provider->do_provider([
                'campaign_id' => $order->campaign_id,
                'order_id'    => 0,
                'taxitems'    => [
                    'taxable_tot' => $taxableAmount,
                    'shipping'    => $shippingAmount,
                    'products'    => $taxProducts,
                ],
                'location'    => $address,
                'cust_id'     => 0,
                'commit'      => false,
                'pre_sale'    => true,
                'shipping_id' => $this->shippingId,
            ]);

            $this->taxAmount          = $tax_provider->total_tax ?? 0;
            $this->salesTaxPercentage = round($tax_provider->tax_rate ?? 0, 2);

            /** Set shipping tax amount */
            if ($tax_provider->tax_shipping) {
                $this->shippingTaxPercentage = $this->salesTaxPercentage;
                $this->shippingTaxAmount     = round($shippingAmount * ($this->salesTaxPercentage / 100), 2);
            }

            /** Set line item tax rate and amount for line item price dropdown */
            if (! empty($tax_provider->lineItemTaxDetails)) {
                foreach ($tax_provider->lineItemTaxDetails as $productTax) {
                    $lineRate = $productTax['tax_rate'] * 100;

                    if ($productTax['sku'] === 'shipping') {
                        $this->shippingTaxAmount     = $productTax['tax_amount'];
                        $this->shippingTaxPercentage = $lineRate;

                        continue;
                    }

                    $nextLineItem = $this->nextLineItems->where('subscriptionId', $productTax['product']['subs_id'])->first();

                    if ($nextLineItem) {
                        $nextLineItem->setTaxRate($lineRate);
                        $nextLineItem->setTaxAmount($productTax['tax_amount']);
                    }
                }
            }

            return $this;
        }

        /** Calculate taxes manually, using sales tax profiles attached to campaign, if applicable */
        if ($order->campaign->salesTaxProfiles()->exists()) {
            $campaignTaxInfo = GetCampaignTaxProfile($address);

            if(! empty($campaignTaxInfo)) {
                $taxLevels     = [
                    \tax\profile::COUNTRY_STATE,
                    \tax\profile::COUNTRY_STATE_COUNTY,
                    \tax\profile::COUNTRY_STATE_COUNTY_CITY,
                ];
                $taxLevelMatch = in_array($campaignTaxInfo['TAX_LEVEL_ID'][0], $taxLevels, true);
                $vatTaxFactor  = 0;
                $vatTaxPct     = 0;

                /** If the match was exact or tax level didn't match, then use the total tax, otherwise use state tax*/
                if (! empty($campaignTaxInfo['EXACT_MATCH'][0]) || ! $taxLevelMatch) {
                    $salesTax = (float) $campaignTaxInfo['TOTAL_TAX'][0];
                } else {
                    $salesTax = (float) $campaignTaxInfo['STATE_TAX'][0];
                }

                $taxPercent = $salesTax / 100;

                /** Determine whether Value-Added Tax is applicable */
                if ((int) $campaignTaxInfo['COUNTRY_ID'][0] === Country::UNITED_KINGDOM_ID) {
                    $vatTaxPct       = (float) ($campaignTaxInfo['VAT_TAX_VAL'][0] ?: 0);
                    $isVatApplicable = false;

                    if (! empty($campaignTaxInfo['MIN_ORDER_VAL'][0])) {
                        switch ($campaignTaxInfo['ORDER_AMT_CASE'][0]) {
                            case 0;
                                $isVatApplicable = true;
                                break;
                            case 1;
                                $isVatApplicable = $taxableAmount >= $campaignTaxInfo['MIN_ORDER_VAL'][0];
                                break;
                            case 2:
                                $isVatApplicable = $taxableAmount < $campaignTaxInfo['MIN_ORDER_VAL'][0];
                                break;
                        }
                    }

                    /** Calculate Value-Added Tax tax amount if applicable */
                    if ($taxableAmount > 0 && $vatTaxPct > 0 && $isVatApplicable) {
                        $vatTaxFactor = $taxableAmount * ($vatTaxPct / 100);
                    }
                }

                if ($taxableAmount || (! empty($campaignTaxInfo['TAX_AFTER_SHIPPING'][0]) && $shippingAmount)) {
                    $taxAmount = $taxableAmount * $taxPercent;

                    /** Calculate taxes on shipping if applicable */
                    if($shippingAmount && ! empty($campaignTaxInfo['TAX_AFTER_SHIPPING'][0])) {
                        $this->shippingTaxPercentage = $salesTax;
                        $this->shippingTaxAmount     = round($shippingAmount * $taxPercent, 2);
                    }

                    $this->salesTaxPercentage = $salesTax;
                    $this->vatTaxPct          = $vatTaxPct;
                    $this->vatTaxFactor       = $vatTaxFactor;
                    /** Calculate total tax amount */
                    $this->taxAmount = round($taxAmount + $this->shippingTaxAmount, 2);

                    /** Set line item tax rate for line item price dropdown */
                    $this->nextLineItems->map(function (NextLineItem $nextLineItem) {
                        $nextLineItem->setTaxRate($this->salesTaxPercentage);

                        if ($this->vatTaxFactor > 0) {
                            $nextLineItem->setVatTaxRate($this->vatTaxPct);
                        }
                    });
                }
            }
        }

        return $this;
    }

    /**
     * As per current re-bill functionality we are using the primary line item to identify few
     * things without looking at the other line items for it.
     *
     * @return \App\Lib\Orders\NextLineItem
     */
    private function getPrimaryNextLineItem(): NextLineItem
    {
        return $this->nextLineItems->first();
    }
}
