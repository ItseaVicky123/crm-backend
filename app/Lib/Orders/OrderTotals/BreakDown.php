<?php

namespace App\Lib\Orders\OrderTotals;

use App\Models\Order;
use App\Models\Shipping;
use Illuminate\Support\Collection;

/**
 * This library takes order and breaks down prices and discounts on a line item level as much as possible
 * to be able to present line item amount combined together
 *
 * @package App\Lib\Orders
 */
class BreakDown
{
    private Order       $order;
    private Collection  $lineItems;
    private ?Collection $lineItemsList = null;
    private ?Shipping   $shipping      = null;

    /* @var int */
    private int  $orderId;
    private ?int $shippingId = null;

    /* @var float */
    private float $shippingAmount           = 0.0;
    private float $taxAmount                = 0.0;
    private float $salesTaxPercentage       = 0.0;
    private float $nonTaxableTotal          = 0.0;
    private float $taxableTotal             = 0.0;
    private float $vatTaxPercentage         = 0.0;
    private float $vatTaxAmount             = 0.0;
    private float $shippingDiscountAmount   = 0.0;
    private float $ceditAmount              = 0.0;
    private float $totalAmount              = 0.0;
    private float $totalDiscountAmount      = 0.0;
    private float $orderLevelDiscountAmount = 0.0;
    private float $lineItemsDiscountAmount  = 0.0;
    private float $lineItemsTotalAmount     = 0.0;
    private float $lineItemsSubtotalAmount  = 0.0;

    /* @var array */
    private array $discountsExcludedFromCalculations = [];
    private array $orderLevelDiscounts               = [];
    private array $lineItemsDiscounts                = [];

    /* @var bool */
    private bool  $shouldFetchBillingModelDiscount = true;
    private bool  $shouldFetchVolumeDiscount       = true;

    /* @var string Discounts */
    public const VOLUME_DISCOUNT  = 'volume';
    public const PREPAID_DISCOUNT = 'prepaid';
    public const RETRY_DISCOUNT   = 'retry';
    public const BM_DISCOUNT      = 'billing_model';
    public const COUPON_DISCOUNT  = 'coupon';
    public const REBILL_DISCOUNT  = 'rebill';

    /**
     * This calculator should be used to calculate a group of subscriptions
     * that belongs to the same recurring date and order.
     * To make sure that the discount calculation will be performed the same way it's done on re-bill
     */
    public function __construct($orderId, ?Order $order = null)
    {
        $this->orderId = (int) $orderId;
        $this->order   = $order ?? Order::readOnly()->find($this->orderId);
    }

    /**
     * @return void
     */
    private function populateLineItemList(): void
    {
        if (! $this->lineItemsList) {
            $this->lineItemsList = $this->order->all_order_items;
        }
    }

    /**
     * @return self
     */
    public function calculate(): self
    {
        $this->populateLineItemList();
        $this->lineItems = collect();

        if (! $this->lineItemsList->count()) {
            return $this;
        }

        // If any order with this subscription ID has this attribute, then current order should not have the BM discount
        // fetch and included in calculation as it's going to mess calculations up and make a wrong unit price assumption
        $this->shouldFetchBillingModelDiscount = ! \App\Models\OrderAttributes\ShouldExcludeBillingModelDiscount::readOnly()
            ->whereHas('order', function ($q) {
                $q->where('subscription_id', $this->order->subscription_id);
            })
            ->exists();

        /**
         * Sorting order where the first one will be main order line item if present.
         * Otherwise, the first inserted upsell line item
         */
        $this->lineItemsList = $this->lineItemsList
            ->sortBy('upsell_orders_id')
            ->values();

        // Create a LineItem object
        foreach ($this->lineItemsList as $lineItem) {
            $this->lineItems->push(new LineItemBreakDown($lineItem));
        }

        return $this
            ->fetchShippingId()
            ->fetchShippingAmount()
            ->fetchDiscounts()
            ->fetchTaxAmount()
            ->fetchOrderLevelDiscountAmount()
            ->fetchLineItemsFinalizedTotals()
            ->fetchDiscountAmount()
            ->fetchTotalAmount();
    }

    /**
     * @return array
     */
    public function getDiscountsExcludedFromCalculations(): array
    {
        return $this->discountsExcludedFromCalculations;
    }

    /**
     * Exclude order level discount from calculation, this is needed so we can still store a discount type and amount
     * even when we are not including those discounts in calculations. We do this because we could not revert
     * line item price back to price before this discount was applied, this usually would happen because we don't have
     * a snapshot of a line item discount amount on a line level instead of the order level
     *
     * @param string $discountName
     */
    public function excludeDiscountFromCalculations(string $discountName): void
    {
        if (! in_array($discountName, $this->getDiscountsExcludedFromCalculations(), true)) {
            $this->discountsExcludedFromCalculations[] = $discountName;
        }
    }

    /**
     * @param string $name
     * @param float $discountAmount
     * @return void
     */
    private function addOrderLevelDiscount(string $name, float $discountAmount): void
    {
        $this->orderLevelDiscounts[$name] = $discountAmount;
    }

    /**
     * @return \App\Models\Shipping|null
     */
    public function getShippingMethod(): ?Shipping
    {
        return $this->shipping;
    }

    /**
     * @return int|null
     */
    public function getShippingId(): ?int
    {
        return $this->shippingId;
    }

    /**
     * @return float
     */
    public function getLineItemsDiscountAmount(): float
    {
        return $this->lineItemsDiscountAmount;
    }

    /**
     * @return float
     */
    public function getLineItemsTotalAmount(): float
    {
        return $this->lineItemsTotalAmount;
    }

    /**
     * Get the order total before credit applied
     *
     * @return float
     */
    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    /**
     * @return float
     */
    public function getTotalCreditAmount(): float
    {
        return $this->ceditAmount;
    }

    /**
     * Get total amount that was built after credit was applied
     *
     * @return float
     */
    public function getTotalBilledAmount(): float
    {
        return round($this->getTotalAmount() - $this->getTotalCreditAmount(), 2);
    }

    /**
     * @return float
     */
    public function getSubtotalAmount(): float
    {
        return round($this->lineItemsSubtotalAmount, 2);
    }

    /**
     * @return float
     */
    public function getShippingAmount(): float
    {
        return $this->shippingAmount;
    }

    /**
     * Get total tax amount (sales tax + VAT)
     *
     * @return float
     */
    public function getTotalTaxAmount(): float
    {
        return $this->getTaxAmount() + $this->getVatTaxAmount();
    }

    /**
     * @return float
     */
    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    /**
     * @return float
     */
    public function getSalesTaxPercentage(): float
    {
        return $this->salesTaxPercentage;
    }

    /**
     * @return float
     */
    public function getNonTaxableTotal(): float
    {
        return $this->nonTaxableTotal;
    }

    /**
     * @return float
     */
    public function getTaxableTotal(): float
    {
        return $this->taxableTotal;
    }

    /**
     * @return float
     */
    public function getVatTaxPercentage(): float
    {
        return $this->vatTaxPercentage;
    }

    /**
     * @return float
     */
    public function getVatTaxAmount(): float
    {
        return $this->vatTaxAmount;
    }

    /**
     * Get all line items on the order including add-ons
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    /**
     * Get all order level discounts
     * FYI: By default this will return discount even if it's excluded from calculation
     * but for this function you can pass a flag that will ignore excluded discounts and will only return you
     * discounts that have been actually included in calculations
     *
     * @param bool $ignoreExclusions
     * @return array
     */
    public function getOrderLevelDiscounts(bool $ignoreExclusions = false): array
    {
        // Remove discounts excluded from calculation
        if ($ignoreExclusions) {
            return array_diff_key($this->orderLevelDiscounts, array_flip($this->getDiscountsExcludedFromCalculations()));
        }

        return $this->orderLevelDiscounts;
    }

    /**
     * Get order level discount amount
     *
     * @return float
     */
    public function getOrderLevelDiscountAmount(): float
    {
        return $this->orderLevelDiscountAmount;
    }

    /**
     * Get all line item discounts
     * FYI: this will return discount even if it's excluded from calculation
     *
     * @return array
     */
    public function getLineItemDiscounts(): array
    {
        return $this->lineItemsDiscounts;
    }

    /**
     * Get all discounts fetched for the order
     * FYI: this will return discount even if it's excluded from calculation
     *
     * @return array
     */
    public function getAllDiscounts(): array
    {
        return array_merge($this->getOrderLevelDiscounts(), $this->getLineItemDiscounts());
    }

    /**
     * Get order-line-teim level discount amount by discount name
     * FYI: this will return a discount even if it's excluded from calculation
     *
     * @param string $name
     * @return float default $0
     */
    public function getDiscount(string $name): float
    {
        return $this->getAllDiscounts()[$name] ?? 0;
    }

    /**
     * Get total disccount applied to the order
     *
     * @return float
     */
    public function getDiscountAmount(): float
    {
        return $this->totalDiscountAmount;
    }

    /**
     * Get Shipping Discount
     *
     * @return float
     */
    public function getShippingDiscountAmount(): float
    {
        return $this->shippingDiscountAmount;
    }

    /**
     * Calculate Shipping total
     *
     * @return float
     */
    public function getShippingTotalAmount(): float
    {
        return max(0, $this->getShippingAmount() - $this->getShippingDiscountAmount());
    }

    /**
     * Get order level discount amount by discount name
     * FYI: this will return a discount even if it's excluded from calculation
     *
     * @param string $name
     * @return float default $0
     */
    public function getOrderLevelDiscount(string $name): float
    {
        return $this->getOrderLevelDiscounts()[$name] ?? 0;
    }

    /**
     * Fetch all order/line-item level discounts
     *
     * Because the aproach we use is to start from line item price after all discounts applied
     * and undo each discount one by one BACKWARDS in the same order in which it was applied
     *
     * @return $this
     */
    private function fetchDiscounts(): self
    {
        return $this
            ->fetchCouponDiscounts()
            ->fetchRetryDiscountAmount()
            ->fetchReBillDiscountAmount()
            ->fetchPrePaidDiscount()
            ->fetchVolumeDiscount()
            ->fetchBillingModelDiscount()
            ->fetchSubscriptionCreditAmount();
    }

    /**
     * Fetch Coupon Discounts
     *
     * @return self
     */
    private function fetchCouponDiscounts(): self
    {
        $shippingDiscountAmount = (float) ($this->order->shipping_coupon_discount->value ?? 0);

        if ($shippingDiscountAmount > 0) {
            // Record coupon shipping discount
            $this->shippingDiscountAmount = $shippingDiscountAmount;
            // Restore price before the discount applied
            $this->shippingAmount += $shippingDiscountAmount;
        }

        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            $productCouponDiscount = (float) ($lineItem->getLineItem()->order_product->product_coupon_discount->value ?? 0);

            if ($productCouponDiscount > 0) {
                $lineItem->addDiscount(self::COUPON_DISCOUNT, $productCouponDiscount);
                // Restore unit price before the discount applied
                $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), bcdiv($productCouponDiscount, $lineItem->getQuantity(), 4), 4));
            }
        });

        return $this;
    }

    /**
     * Fetch Retry Discount
     *
     * @return $this
     */
    private function fetchRetryDiscountAmount(): self
    {
        $retryDiscountAmount = (float) ($this->order->retry_discount_amt ?? 0);

        if ($retryDiscountAmount > 0) {
            $retryPercent = bcdiv($this->order->retry_discount_pct, 100, 4);

            // Because we are calculating line item discount manually, we need to consider round up and down options
            $totalDiscountAmountRoundUp   = 0;
            $totalDiscountAmountRoundDown = 0;
            $totalDiscountAmountUnitPrice = 0;

            $this->lineItems->map(function (LineItemBreakDown $lineItem) use (
                $retryPercent,
                &$totalDiscountAmountRoundUp,
                &$totalDiscountAmountRoundDown,
                &$totalDiscountAmountUnitPrice
            ) {
                // If this is the only discount applied on the order, then the unit price is expected to be correct
                $unitPrice = $lineItem->getLineItem()->order_product->order_product_unit_price->value ?? 0;
                // Calculate total order discount based on the unit price as a price before any discount is applied
                $totalDiscountAmountUnitPrice += round($unitPrice * $lineItem->getQuantity() * $retryPercent, 2);

                // Line item subtotal with rebill discount applied
                $discountedTotal = $lineItem->getSubtotal();
                // Devide discounted price by inversed persentage discount to get price before the discount
                $total = bcdiv($discountedTotal, bcsub(1, $retryPercent, 4), 4);

                // Rounded Up price without discount
                $totalRoundUp = bcdiv(ceil(bcmul($total, 100, 2)), 100, 2);
                // Calculate Rounded Up discount amount. Differance between price with and without discount
                $discountAmountRoundUp = bcsub($totalRoundUp, $discountedTotal, 4);
                // Calculate Rounded Up order discount amount
                $totalDiscountAmountRoundUp = bcadd($totalDiscountAmountRoundUp, $discountAmountRoundUp, 4);

                // Rounded Down price without discount
                $totalRoundDown = round($total, 2);
                // Calculate Rounded Down discount amount. Differance between price with and without discount
                $discountAmountRoundDown = bcsub($totalRoundDown, $discountedTotal, 4);
                // Calculate Rounded Down order discount amount
                $totalDiscountAmountRoundDown = bcadd($totalDiscountAmountRoundDown, $discountAmountRoundDown, 4);
            });

            $shouldRoundUp      = round($totalDiscountAmountRoundUp, 2) === $retryDiscountAmount;
            $shouldRoundDown    = round($totalDiscountAmountRoundDown, 2) === $retryDiscountAmount;
            $shouldUseUnitPrice = $totalDiscountAmountUnitPrice === $retryDiscountAmount;

            // If price didn't match nether of options, this is most likely due to retry shipping discount that is missing
            if (
                (
                    ! $shouldUseUnitPrice
                    || ! $shouldRoundUp
                    || ! $shouldRoundDown
                )
                && $this->getShippingAmount() > 0
            ) {
                // Checking history notes to see if the shipping was discounted
                $isShippingDiscounted = $this->order
                    ->history_notes()
                    ->where('type', 'history-note-stepdown-discount')
                    ->where('status', 'like', '% and shipping cost')
                    ->exists();

                if ($isShippingDiscounted) {
                    // Devide discounted shipping price by inversed persentage discount to get price before the discount
                    $shippingPrice = bcdiv($this->getShippingAmount(), bcsub(1, $retryPercent, 4), 4);
                    // Calculate discount amount. Differance between price with and without discount
                    $shippingDiscount = bcsub($shippingPrice, $this->getShippingAmount(), 4);

                    // Calculate Rounded Up order discount amount
                    $shippingDiscountRoundUp    = bcdiv(ceil(bcmul($shippingDiscount, 100, 2)), 100, 2);
                    $totalDiscountAmountRoundUp = bcadd($totalDiscountAmountRoundUp, $shippingDiscountRoundUp, 4);

                    // Calculate Rounded Down order discount amount
                    $shippingDiscountRoundDown    = round($shippingDiscount, 2);
                    $totalDiscountAmountRoundDown = bcadd($totalDiscountAmountRoundDown, $shippingDiscountRoundDown, 4);

                    // Calculate Unit Price Discount with shipping included
                    $totalDiscountAmountUnitPrice += $shippingDiscountRoundDown;

                    // Recalculate based on the new change
                    $shouldRoundUp      = round($totalDiscountAmountRoundUp, 2) === $retryDiscountAmount;
                    $shouldRoundDown    = round($totalDiscountAmountRoundDown, 2) === $retryDiscountAmount;
                    $shouldUseUnitPrice = $totalDiscountAmountUnitPrice === $retryDiscountAmount;

                    // Restore shipping discount for the shipping price and record discount amount
                    $this->shippingAmount         = round($shippingPrice, 2);
                    $shippingDiscount             = ! $shouldUseUnitPrice && $shouldRoundUp ? $shippingDiscountRoundUp : $shippingDiscountRoundDown;
                    $this->shippingDiscountAmount = bcadd($this->getShippingDiscountAmount(), $shippingDiscount, 2);
                }
            }

            /**
             * If we were able to restore exact retry discount on a line item level, then store this discount for each line
             * Otherwise use order level discount that is already store into the order's total table
             */
            if ($shouldRoundUp || $shouldRoundDown || $shouldUseUnitPrice) {
                $this->lineItems->map(function (LineItemBreakDown $lineItem) use (
                    $retryPercent,
                    $shouldRoundUp,
                    $shouldUseUnitPrice
                ) {
                    // Check if we can just use unit price to calculate line item retry discount
                    if ($shouldUseUnitPrice) {
                        // If this is the only discount applied on the order, then the unit price is expected to be correct
                        $unitPrice           = $lineItem->getLineItem()->order_product->order_product_unit_price->value ?? 0;
                        $retryDiscountAmount = round($unitPrice * $lineItem->getQuantity() * $retryPercent, 2);
                    } else {
                        // Devide discounted price by inversed persentage discount to get price before the discount
                        $subtotalBeforeDiscount = bcdiv($lineItem->getSubtotal(), bcsub(1, $retryPercent, 3), 3);

                        // Check if we should use round up or round down path
                        if ($shouldRoundUp) {
                            $subtotalBeforeDiscount = bcdiv(ceil(bcmul($subtotalBeforeDiscount, 100)), 100, 3);
                        } else {
                            $subtotalBeforeDiscount = round($subtotalBeforeDiscount, 2);
                        }

                        // Calculate discount amount. Differance between price with and without discount
                        $retryDiscountAmount = bcsub($subtotalBeforeDiscount, $lineItem->getSubtotal(), 4);
                    }

                    if ($retryDiscountAmount > 0) {
                        $lineItem->addDiscount(
                            self::RETRY_DISCOUNT,
                            $retryDiscountAmount
                        );

                        // Set unit price before the discount was applied
                        $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), bcdiv($retryDiscountAmount, $lineItem->getQuantity(), 4), 4));
                    }
                });
            } else {
                $this->addOrderLevelDiscount(self::RETRY_DISCOUNT, $retryDiscountAmount);
                $this->excludeDiscountFromCalculations(self::RETRY_DISCOUNT);
            }
        }

        return $this;
    }

    /**
     * Fetch Re-Bill Discount
     *
     * @return $this
     */
    private function fetchReBillDiscountAmount(): self
    {
        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            $reBillDiscountAmount = (float) ($lineItem->getLineItem()->rebill_discount_amount->value ?? 0);

            if ($reBillDiscountAmount > 0) {
                $lineItem->addDiscount(self::REBILL_DISCOUNT, $reBillDiscountAmount);

                // Restore price before Rebill Discount applied
                $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), bcdiv($reBillDiscountAmount, $lineItem->getQuantity(), 4), 4));
            }
        });

        return $this;
    }

    /**
     * Fetch Prepaid discount
     *
     * @return $this
     */
    private function fetchPrePaidDiscount(): self
    {
        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            if ($lineItem->isPrepaid()) {
                // Prepaid offer is not compatible with Volume Discount. Do not even fetch it
                $this->shouldFetchVolumeDiscount = false;
                // Devide base unit price by prepaid cycltes to get base unit price for 1 cycle
                // We should maintain the order of how we did it during order creation
                $lineItem->setBaseUnitPrice(bcdiv($lineItem->getBaseUnitPrice(), $lineItem->getPrepaidCycles(), 4));
            }

            $prePaidDiscountAmount = (float) ($lineItem->getLineItem()->prepaid_discount->value ?? 0);

            if ($prePaidDiscountAmount > 0) {
                $lineItem->addDiscount(self::PREPAID_DISCOUNT, $prePaidDiscountAmount);

                // Unit discount amoun for 1 cycle and quantity of 1
                $unitPrepaidDiscount = bcdiv($prePaidDiscountAmount, $lineItem->getQuantity() * $lineItem->getPrepaidCycles(), 4);

                // Restrore unit price before Prepaid Discount applied
                $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), $unitPrepaidDiscount, 4));
            }
        });

        return $this;
    }

    /**
     * Fetch Volume Discount
     *
     * @return $this
     */
    private function fetchVolumeDiscount(): self
    {
        if (! $this->shouldFetchVolumeDiscount) {
            return $this;
        }

        $volumeDiscountAmount = (float) ($this->order->volume_discount->value ?? 0);

        if ($volumeDiscountAmount > 0) {
            // Only if we have 1 product we can restore the line item discount, otherwise we would do an order level
            if ($this->lineItems->count() > 1) {
                $this->addOrderLevelDiscount(self::VOLUME_DISCOUNT, round($volumeDiscountAmount, 2));
                // Because we use price after all discount applied and we weren't able to restore VD we should exclude it
                $this->excludeDiscountFromCalculations(self::VOLUME_DISCOUNT);
            } else {
                /** @var \App\Lib\Orders\OrderTotals\LineItemBreakDown $lineItem */
                $lineItem = $this->lineItems->first();
                $lineItem->addDiscount(self::VOLUME_DISCOUNT, $volumeDiscountAmount);
                // Restore Price before Volume Discount
                $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), bcdiv($volumeDiscountAmount, $lineItem->getQuantity(), 4), 4));
            }
        }

        return $this;
    }

    /**
     * Fetch Billing Model Discount
     *
     * @return $this
     */
    private function fetchBillingModelDiscount(): self
    {
        if (! $this->shouldFetchBillingModelDiscount) {
            return $this;
        }

        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            if (! $lineItem->isPrepaid()) {
                $lineItemBilingModelDiscount = (float) ($lineItem->getLineItem()->order_product->billing_model_discount->value ?? 0);

                if ($lineItemBilingModelDiscount > 0) {
                    $lineItem->setBaseUnitPrice(bcadd($lineItem->getBaseUnitPrice(), $lineItemBilingModelDiscount, 4));
                    $lineItem->addDiscount(self::BM_DISCOUNT, bcmul($lineItemBilingModelDiscount, $lineItem->getQuantity(), 4));
                }
            }
        });

        return $this;
    }

    /**
     * Fetch Subscription Credit
     *
     * @return $this
     */
    private function fetchSubscriptionCreditAmount(): self
    {
        $this->ceditAmount = 0;

        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            $this->ceditAmount += (float) ($lineItem->getLineItem()->order_product->billing_model_subscription_credit->value ?? 0);
        });

        return $this;
    }

    /**
     * Fetch shipping amount
     *
     * @return self
     */
    private function fetchShippingAmount(): self
    {
        $this->shippingAmount = $this->order->shipping_amount->value ?? 0;

        return $this;
    }

    /**
     * Fetch Tax Amount
     *
     * @return $this
     */
    private function fetchTaxAmount(): self
    {
        $this->taxAmount          = $this->order->tax_amount->value ?? 0;
        $this->salesTaxPercentage = $this->order->tax_percent->value ?? 0;
        $this->nonTaxableTotal    = $this->order->non_taxable_total->value ?? 0;
        $this->taxableTotal       = $this->order->taxable_total->value ?? 0;
        $this->vatTaxPercentage   = $this->order->vat_tax_percent->value ?? 0;
        $this->vatTaxAmount       = $this->order->vat_tax_amount->value ?? 0;

        return $this;
    }

    /**
     * Fetch shipping id and method
     *
     * @return $this
     */
    private function fetchShippingId(): self
    {
        $this->shippingId = $this->order->ship_method->id ?? null;
        $this->shipping   = $this->order->ship_method;

        return $this;
    }

    /**
     * Fetch total discount amount
     *
     * @return self
     */
    private function fetchDiscountAmount(): self
    {
        $this->totalDiscountAmount = round($this->getOrderLevelDiscountAmount() + $this->getLineItemsDiscountAmount(), 2);

        return $this;
    }

    /**
     * Fetch total amount
     *
     * @return self
     */
    private function fetchTotalAmount(): self
    {
        // Get total of all line items after line item discounts applied
        $this->totalAmount = $this->getLineItemsTotalAmount();
        // Apply order level discounts
        $this->totalAmount -= $this->getOrderLevelDiscountAmount();
        // Add shipping and taxes
        $this->totalAmount += $this->getShippingTotalAmount() + $this->getTotalTaxAmount();
        // Round up result
        $this->totalAmount = round($this->totalAmount, 2);
        // Get the actual (currect) order's total for the comparison
        $actualTotal = round($this->order->total_revenue, 2);

        // If the actual total didn't match calculated total, log it in for farther investigations and improvements
        if ($this->totalAmount !== $actualTotal) {
            $offBy = $actualTotal - $this->totalAmount;
            $this->logCalculationData("mismatched the actual order's total for order ID {$this->orderId}. Expected: {$actualTotal}. Current: {$this->totalAmount}. Off by: {$offBy}");

            $this->totalAmount = $actualTotal;
        }

        return $this;
    }

    /**
     * Fetch Order Level Discounts Amount
     *
     * @return self
     */
    private function fetchOrderLevelDiscountAmount(): self
    {
        $this->orderLevelDiscountAmount = 0.0;

        foreach ($this->getOrderLevelDiscounts(true) as $discountAmount) {
            $this->orderLevelDiscountAmount += $discountAmount;
        }

        return $this;
    }

    /**
     * Fetch Combined Line Items totals:
     * - subtotal
     * - discounts list
     * - discount amount
     * - total
     *
     * @return self
     */
    private function fetchLineItemsFinalizedTotals(): self
    {
        $this->lineItemsSubtotalAmount = 0.0;
        $this->lineItemsDiscountAmount = 0.0;
        $this->lineItemsTotalAmount    = 0.0;
        $this->lineItemsDiscounts      = [];

        // Add up all line item totals
        $this->lineItems->map(function (LineItemBreakDown $lineItem) {
            $this->lineItemsSubtotalAmount += $lineItem->getSubtotal();
            $this->lineItemsTotalAmount    += $lineItem->fetchTotalAmount()->getTotal();
            $this->lineItemsDiscountAmount += $lineItem->getDiscountAmount();

            foreach ($lineItem->getDiscounts() as $name => $amount) {
                $this->lineItemsDiscounts[$name] = round(($this->lineItemsDiscounts[$name] ?? 0) + $amount, 2);
            }
        });

        return $this;
    }

    /**
     * Log calculator data to debug occured issue easier
     *
     * @param string $message
     * @return void
     */
    private function logCalculationData(string $message): void
    {
        $message = "Order BreakDown Calculator {$message}";

        try {
            \fileLogger::log_warning(
                $message,
                [
                    'total'                    => $this->getTotalAmount(),
                    'total_billed'             => $this->getTotalBilledAmount(),
                    'subtotal'                 => $this->getSubtotalAmount(),
                    'shipping_amount'          => $this->getShippingAmount(),
                    'shipping_discount_amount' => $this->getShippingDiscountAmount(),
                    'shipping_total_amount'    => $this->getShippingTotalAmount(),
                    'discount_amount'          => $this->getDiscountAmount(),
                    'credit'                   => $this->getTotalCreditAmount(),
                    'tax_amount'               => $this->getTaxAmount(),
                    'tax_%'                    => $this->getSalesTaxPercentage(),
                    'discounts'                => $this->getAllDiscounts(),
                    'excluded_discounts'       => $this->getDiscountsExcludedFromCalculations(),
                    'line_items'               => $this->lineItems->map(fn(LineItemBreakDown $lineItem) => [
                        'product_id'      => $lineItem->getProductId(),
                        'total'           => $lineItem->getTotal(),
                        'sub_total'       => $lineItem->getSubtotal(),
                        'unit_price'      => $lineItem->getBaseUnitPrice(),
                        'qty'             => $lineItem->getQuantity(),
                        'discount_amount' => $lineItem->getDiscountAmount(),
                        'discounts'       => $lineItem->getDiscounts(),
                        'prepaid_cycles'  => $lineItem->getPrepaidCycles(),
                    ]),
                ]
            );
        } catch (\Exception $e) {
            \fileLogger::log_error(__METHOD__ . "Logging error. {$message}", $e->getMessage());
        }
    }
}
