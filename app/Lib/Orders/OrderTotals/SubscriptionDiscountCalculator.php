<?php

namespace App\Lib\Orders\OrderTotals;

use App\Models\Offer\BillingModelDiscount;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Class SubscriptionDiscountCalculator
 * @package App\Lib\Orders\OrderTotals
 */
class SubscriptionDiscountCalculator
{
    /**
     * @var BillingModelDiscount $billingModelDiscount
     */
    protected BillingModelDiscount $billingModelDiscount;

    /**
     * @var bool $hasDiscount
     */
    protected bool $hasDiscount = false;

    /**
     * SubscriptionDiscountCalculator constructor.
     * @param BillingModelDiscount $billingModelDiscount
     */
    public function __construct(BillingModelDiscount $billingModelDiscount)
    {
        $this->billingModelDiscount = $billingModelDiscount;
        $this->hasDiscount          = $this->billingModelDiscount->hasDiscount();
    }

    /**
     * @return float
     */
    public function getDiscountPercent(): float
    {
        return (float) $this->billingModelDiscount->percent;
    }

    /**
     * @return float
     */
    public function getDiscountAmount(): float
    {
        return (float) $this->billingModelDiscount->amount;
    }

    /**
     * @return bool
     */
    public function isHasDiscount(): bool
    {
        return $this->hasDiscount;
    }

    /**
     * Calculate the unit price for a given product.
     * @param Product $product
     * @param int|null $variantId
     * @param float|null $basePrice
     * @return float|null
     */
    public function calculatedUnitPrice(Product $product, ?int $variantId = null, ?float $basePrice = null): ?float
    {
        // This does not account for bundles, it is expected that the bundle price be passed in as
        // $basePrice
        //
        if (is_null($basePrice)) {
            if ($variantId) {
                $variant   = ProductVariant::findOrFail($variantId);
                $basePrice = $variant->getPrice();
            } else {
                $basePrice = $product->price;
            }
        }

        $price            = $basePrice;
        $mainProductPrice = $basePrice;
        $percent          = $this->getDiscountPercent();
        $amountOff        = null;

        if ($percent > 0 && $percent <= 100) {
            $amountOff = round(($percent / 100) * $mainProductPrice, 2);
        } else if ($flatDiscount = $this->getDiscountAmount()) {
            $amountOff = round($flatDiscount, 2);
        }

        if ($amountOff && $mainProductPrice) {
            $price = round(max(0, $mainProductPrice - $amountOff), 2);
        }

        return $price;
    }
}
