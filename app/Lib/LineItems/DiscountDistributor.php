<?php

namespace App\Lib\LineItems;

use Illuminate\Support\Collection;

/**
 * Class DiscountDistributor
 * @package App\Lib\LineItems
 */
class DiscountDistributor
{
    /**
     * @var array $lineItemMap
     */
    protected array $lineItemMap;

    /**
     * DiscountDistributor constructor.
     * @param array $lineItemMap
     */
    public function __construct(array $lineItemMap)
    {
        $this->lineItemMap = $lineItemMap;
    }

    /**
     * Get the line item map after discounts have been applied.
     *
     * @param float $totalDiscountAmount
     * @param float $percentOff
     * @return Collection
     */
    public function getDiscountedMap(float $totalDiscountAmount, float $percentOff = 0): Collection
    {
        return $percentOff > 0
            ? $collection = $this->getDiscountedMapPercent($totalDiscountAmount, $percentOff)
            : $this->getDiscountedMapFlat($totalDiscountAmount);
    }

    /**
     * Get the line item map after discounts have been applied.
     * @param float $totalDiscountAmount
     * @return Collection
     */
    public function getDiscountedMapFlat(float $totalDiscountAmount): Collection
    {
        $map              = $this->lineItemMap;
        $numLineItems     = count($this->lineItemMap);
        $numFreeLineItems = 0;
        $discountAmount   = $totalDiscountAmount; // Start with the total discount amount
        $remainder        = 0;

        while ($discountAmount > 0) {
            if ($numFreeLineItems === $numLineItems) {
                $remainder = $discountAmount;
                break;
            }

            $discountedMap = [];

            if ($amountOffPerItem = $this->calculatedAmountOffPerItem($discountAmount, $numFreeLineItems)) {
                foreach ($map as $uuid => $subtotal) {
                    if ($subtotal > 0) {
                        if ($subtotal <= $amountOffPerItem) {
                            // If the new subtotal becomes negative or 0 make it free.
                            // The deduction will just be the item subtotal.
                            //
                            $newSubtotal    = 0;
                            $amountToDeduct = round($subtotal, 4);
                            $numFreeLineItems++;
                        } else {
                            $newSubtotal    = round($subtotal - $amountOffPerItem, 4);
                            $amountToDeduct = $amountOffPerItem;
                        }

                        // Deduct the calculated deduction from the working discount amount.
                        //
                        $discountAmount       = max(0, round($discountAmount - $amountToDeduct, 4));
                        $discountedMap[$uuid] = $newSubtotal;

                        // Override the map with the discount map to update the values to the latest
                        // discounted price.
                        //
                        foreach ($discountedMap as $idx => $discountedSubtotal) {
                            $map[$idx] = $discountedSubtotal;
                        }
                    }
                }
            } else {
                // If this becomes 0 because the number is so small it is no longer divisible by the number of eligible items
                // Then break out
                //
                break;
            }
        }

        return collect([
            'map'            => $map,
            'remainder'      => $remainder,
            'unit_discounts' => $this->calculatedUnitPriceDiscountMap($map),
        ]);
    }

    /**
     * Get the line item map after discounts have been applied.
     *
     * @param float $totalDiscountAmount
     * @param float $percentOff
     * @return Collection
     */
    public function getDiscountedMapPercent(float $totalDiscountAmount, float $percentOff): Collection
    {
        foreach ($this->lineItemMap as $uuid => $subtotal) {
            $discountedMap[$uuid] = round(($percentOff / 100) * $subtotal, 4);;

            // Override the map with the discount map to update the values to the latest
            // discounted price.
            //
            foreach ($discountedMap as $idx => $discountedSubtotal) {
                $map[$idx] = $this->lineItemMap[$idx] - $discountedSubtotal;
            }
        }
        return collect([
            'map'            => $map,
            'remainder'      => 0, // we will not have remainder when discount is percent
            'unit_discounts' => $this->calculatedUnitPriceDiscountMap($map),
        ]);
    }

    /**
     * @param float $discountAmount
     * @param int $numFreeLineItems
     * @return float
     */
    private function calculatedAmountOffPerItem(float $discountAmount, int $numFreeLineItems): float
    {
        return round($discountAmount / (count($this->lineItemMap) - $numFreeLineItems), 4);
    }

    /**
     * Calculate the true discount per unit price.
     * @param array $discountedMap
     * @return array
     */
    private function calculatedUnitPriceDiscountMap(array $discountedMap): array
    {
        $unitPriceDiscountMap = [];

        foreach ($this->lineItemMap as $uuid => $originalUnitPrice) {
            if (isset($discountedMap[$uuid])) {
                $unitPriceDiscountMap[$uuid] = round($originalUnitPrice - $discountedMap[$uuid], 4);
            }
        }

        return $unitPriceDiscountMap;
    }
}
