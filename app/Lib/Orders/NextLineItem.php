<?php

namespace App\Lib\Orders;

use App\Facades\SMC;
use App\Lib\BillingModels\ShippingPriceCalculationInput;
use App\Models\OrderProductBundle;
use App\Models\Product;
use App\Models\ProductPriceType;
use App\Models\ProductVariant;
use App\Models\Shipping;
use App\Models\Subscription;
use App\Models\TrialWorkflow\TrialWorkflowLineItem;
use App\Models\TrialWorkflow\TrialWorkflowUnit;
use billing_models\api\order_product_entry;
use Illuminate\Support\Collection;

/**
 * Class NextLineItem
 *
 * Using this class you will be able to find out:
 * - Next Recurring Product
 * - Next Recurring Variant (if applicable)
 * - Next Recurring Price
 * - Next Recurring Quantity
 * the same way we so that on re-bill
 *
 * @required MAKE SURE to maintain any changes that are made to the re-bill here
 * @package App\Lib\Orders
 */
class NextLineItem
{
    private ?Subscription     $lineItem;
    private ?TrialWorkflowUnit $nextTrialWorkflowUnit = null;
    private ?Product          $product              = null;
    private ?ProductVariant   $variant              = null;
    private ?Collection       $children             = null;

    private float $unitPrice    = 0.0;

    private int   $quantity     = 0;

    private float $taxRate      = 0.0;

    private float $taxAmount    = 0.0;

    private float $vatTaxRate   = 0.0;

    private float $vatTaxAmount = 0.0;

    private ?int    $productId      = null;
    private ?int    $variantId      = null;
    public  ?string $subscriptionId = null;

    protected array $discounts = [];

    private bool $shouldCalculateBillingModelDiscount = false;

    public function __construct(Subscription $lineItem)
    {
        $this->lineItem       = $lineItem;
        $this->subscriptionId = $this->getLineItem()->subscription_id;

        $this
            ->determineNextProduct()
            ->determineNextQuantity()
            ->calculateUnitPrice();
    }

    public function isBundle(): bool
    {
        return $this->getProduct()->is_bundle;
    }

    public function getChildren(): Collection
    {
        if (! $this->children) {
            $this->children = OrderProductBundle::readOnly()
                ->where([
                    'order_id'      => $this->getLineItem()->order_id,
                    'bundle_id'     => $this->getProductId(),
                    'main_flag'      => $this->getLineItem()->isMain(),
                    'is_next_cycle' => 1,
                ])->get();
        }

        return $this->children;
    }

    public function addDiscount(string $name, float $discountAmount): void
    {
        $this->discounts[$name] = $discountAmount;
    }

    public function getDiscountAmount(): float
    {
        $discountAmount = 0.0;

        foreach ($this->getDiscounts() as $discount) {
            $discountAmount += $discount;
        }

        return $discountAmount;
    }

    public function getDiscount(string $name): float
    {
        return $this->getDiscounts()[$name] ?? 0;
    }

    public function resetDiscounts(): void
    {
        $this->discounts = [];
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getSubtotal(): float
    {
        return $this->getUnitPrice() * $this->getQuantity();
    }

    public function getLineItem(): Subscription
    {
        return $this->lineItem;
    }

    public function getTotal(): float
    {
        $total = $this->getSubtotal() - $this->getDiscountAmount();

        return round(max(0, $total), 2);
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function shouldCalculateBillingModelDiscount(): bool
    {
        return $this->shouldCalculateBillingModelDiscount;
    }

    public function setShouldCalculateBillingModelDiscount(bool $shouldCalculate): bool
    {
        return $this->shouldCalculateBillingModelDiscount = $shouldCalculate;
    }

    public function isVariant(): bool
    {
        return (bool) $this->variantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProduct(): Product
    {
        if (! $this->product) {
            $product = Product::readOnly()
                ->without(['meta'])
                ->findOrFail($this->getProductId());
            $this->setProduct($product);
        }

        return $this->product;
    }

    public function getVariant(): ?ProductVariant
    {
        if (! $this->isVariant()) {
            return null;
        }

        if (! $this->variant) {
            $variant = ProductVariant::readOnly()->find($this->getVariantId());
            $this->setVariant($variant);
        }

        return $this->variant;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function setVatTaxRate(float $vatTaxRate): void
    {
        $this->vatTaxRate = $vatTaxRate;
    }

    public function setTaxAmount(float $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    public function getTaxAmount(): float
    {
        // Use tax amount override if present
        if ($this->taxAmount) {
            return $this->taxAmount;
        }

        // Get tax amount on a fly
        return $this->getTotal() * ($this->getTaxRate() / 100);
    }

    public function getVatTaxAmount(): float
    {
        // Use vat tax amount override if present
        if ($this->vatTaxAmount) {
            return $this->vatTaxAmount;
        }

        // Get tax amount on a fly
        return $this->getTotal() * ($this->getVatTaxRate() / 100);
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getVatTaxRate(): float
    {
        return $this->vatTaxRate;
    }

    /**
     * Calculate Trial Workflow shipping amount if applicable
     *
     * @param \App\Models\Shipping $shippingModel
     * @param float $amount
     * @return float|null
     */
    public function getTrialWorkflowShippingAmount(Shipping $shippingModel, float $amount): ?float
    {
        if (! $nextTrialWorkflowUnit = $this->getNextTrialWorkflowUnit()) {
            return null;
        }

        return $nextTrialWorkflowUnit->getUnitShippingPrice(
            new ShippingPriceCalculationInput([
                'defaultPrice'  => $amount,
                'shippingModel' => $shippingModel,
            ])
        );
    }

    /**
     * Get shipping override amount if applicable
     *
     * @return float|null
     */
    public function getShippingOverrideAmount(): ?float
    {
        $nextShippingAmount = $this->getLineItem()->order_subscription->next_recurring_shipping ?? null;

        if (is_null($nextShippingAmount)) {
            return null;
        }

        // Shipping override amount is split between all quantities, so multiply it
        return $nextShippingAmount * $this->getQuantity();
    }

    /**
     * Find out what's the next recurring product is going to be
     *
     * @return $this
     */
    private function determineNextProduct(): self
    {
        $lineItem = $this->getLineItem();

        // Use billing model order information if applicable
        if ($billingModelOrder = $lineItem->order_subscription) {
            $this->setProductId($billingModelOrder->next_recurring_product);
            $this->setVariantId($billingModelOrder->next_recurring_variant ?: null);

            return $this;
        }

        // Determine Next Recurring Product using Legacy way
        $orderProduct = $lineItem->order_product;
        $product      = $orderProduct->product;
        $isAnAddOn    = $lineItem->isUpsell() && $lineItem->is_add_on;

        if (! $isAnAddOn && $product->recur_product_id > 0) {
            // Use next recurring product override if applicable
            if ($lineItem->custom_rec_prod_id) {
                $this->setProductId($lineItem->custom_rec_prod_id);
            } else {
                // Use next recurring product set on current product
                $this->setProductId($product->recur_product_id);
            }
        } else {
            // Use current product as default then
            $this->setProductId($orderProduct->product_id);
        }

        // Use variant override, if applicable
        if ($lineItem->custom_variant_id) {
            $this->setVariantId($lineItem->custom_variant_id);
            return $this;
        }

        if ($orderProduct->variant_id) {
            /** @var ProductVariant $variant */
            $variant = $this->getProduct()
                ->variants()
                ->find($orderProduct->variant_id);

            // Determine Next Variant, if applicable
            if ($variant) {
                $this->setVariantId($variant->id);
                $this->setVariant($variant);
            }
        }

        return $this;
    }

    /**
     * Find out what's the next recurring quantity is going to be
     *
     * @return self
     */
    private function determineNextQuantity(): self
    {
        $this->setQuantity(1);
        $lineItem = $this->getLineItem();

        if ($billingModelOrder = $lineItem->order_subscription) {
            if ($billingModelOrder->next_recurring_quantity) {
                $isChildMainQuantity = ($billingModelOrder->cycle_depth + 1) >= order_product_entry::DEPTH_MAIN && $billingModelOrder->main_product_quantity;

                if ($billingModelOrder->preserve_quantity || $billingModelOrder->updated_at || $isChildMainQuantity) {
                    $this->setQuantity($billingModelOrder->next_recurring_quantity);
                }
            }

            return $this;
        }

        /** Legacy mode */
        if ($this->getProduct()->is_qty_preserved) {
            $this->setQuantity($lineItem->order_product->quantity);
        }

        return $this;
    }

    /**
     * Find out what's the next recurring unit price is going to be
     *
     * @return void
     */
    private function calculateUnitPrice(): void
    {
        // By default, the price should be ether variant or product price
        $variantPrice = $this->getVariant()->price ?? null;
        $lineItem     = $this->getLineItem();
        $this->setUnitPrice($variantPrice ?? $this->getProduct()->price ?? 0.0);

        // Determine next price for Billing Model Order
        if ($subOrder = $lineItem->order_subscription) {
            $isVdSmcOn = \system_module_control::check(SMC::VOLUME_DISCOUNTS);
            // Use pre-calculated Volume Discounted Price if applicable. Billing model discount is included
            if ($isVdSmcOn && $volumeDiscountedUnitPrice = $this->getLineItem()->nextLineItemVolumeDiscount->value ?? 0) {
                $this->setUnitPrice($volumeDiscountedUnitPrice);

                return;
            }

            $trialWorkflowPrice = $this->getTrialWorkflowPrice();

            // Use Delay Billing Price if applicable
            if (
                is_null($trialWorkflowPrice) &&
                $subOrder->cycle_depth === order_product_entry::DEPTH_TRIAL_DELAY &&
                $subOrder->offer->delayed_billing_flag &&
                $subOrder->offer->delayed_billing_price_flag
            ) {
                $this->setUnitPrice($subOrder->offer->delayed_billing_price);

                return;
            }

            // Calculate bundle price, if applicable
            if ($this->isBundle()) {
                $this->setUnitPrice($this->getBundlePrice());
            }

            $isPrepaidSmcOn = \system_module_control::check(SMC::OFFER_PREPAID);
            // Calculate prepaid unit price, if applicable
            if ($isPrepaidSmcOn && $subOrder->offer->typeIsPrepaid()) {
                // Disable Billing Model Discount for prepaid offer
                $this->setShouldCalculateBillingModelDiscount(false);

                // If this is the last cycle then we should charge the product price multiplied by amount of cycles
                if ($subOrder->prepaid_cycles === $subOrder->current_prepaid_cycle) {
                    $this->setUnitPrice(round(($subOrder->next_recurring_price ?? $this->getUnitPrice()) * $subOrder->prepaid_cycles, 2));
                } else {
                    // If this cycle is not the last one, then the price should be $0 as it was already prepaid
                    $this->setUnitPrice(0);
                }

                return;
            }

            $hasBillingModelDiscount = ($subOrder->sticky_discount_percent ?: $subOrder->sticky_discount_flat_amount) > 0;

            if (! is_null($subOrder->next_recurring_price)) {
                // If price is not preserved, Next Recurring Price DOES NOT include BM discount, so enable it
                if ($hasBillingModelDiscount && ! $subOrder->is_preserve_price) {
                    $this->setUnitPrice($subOrder->next_recurring_price);

                    // Enable BM Discount calculation
                    $this->setShouldCalculateBillingModelDiscount(true);

                    return;
                }

                // Next Recurring Price conditionally includes BM discount, under certain conditions
                if ($subOrder->is_preserve_price) {
                    $this->setUnitPrice($subOrder->next_recurring_price);

                    /**
                     * For custom preserved price subscriptions that have BM discount added to it,
                     * we should calculate BM discount separately for new orders that have been created properly
                     * that don't have the ShouldExcludeBillingModelDiscount attribute attached to them
                     */
                    if (
                        $hasBillingModelDiscount &&
                        ! \App\Models\OrderAttributes\ShouldExcludeBillingModelDiscount::forOrder($this->getLineItem()->order_id)->exists()
                    ) {
                        $this->setShouldCalculateBillingModelDiscount(true);
                    }

                    return;
                }

                // Addon price
                if ($lineItem->isUpsell() && $lineItem->is_add_on) {
                    $this->setUnitPrice($subOrder->next_recurring_price);

                    return;
                }

                // Use Trial Workflow Price if applicable
                if (! is_null($trialWorkflowPrice)) {
                    $this->setUnitPrice($trialWorkflowPrice);

                    return;
                }

                $this->setUnitPrice($variantPrice ?? $subOrder->next_recurring_price);

                return;
            }

            // Enable BM Discount calculation if applicable
            if ($hasBillingModelDiscount) {
                $this->setShouldCalculateBillingModelDiscount(true);
            }
        }
    }

    protected function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    protected function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    protected function setVariantId(?int $variantId): void
    {
        $this->variantId = $variantId;
    }

    protected function setVariant(?ProductVariant $variant): void
    {
        $this->variant = $variant;
    }

    protected function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    private function setUnitPrice(?float $unitPrice): void
    {
        $this->unitPrice = $unitPrice ?? 0.0;
    }

    private function getBundlePrice(): float
    {
        $bundlePrice    = 0;
        $this->children = null;

        // If price type is fixed, then use product's price
        if ($this->getProduct()->price_type_id === ProductPriceType::FIXED) {
            return $this->getProduct()->price;
        }

        foreach ($this->getChildren() as $child) {
            $unitPrice = null;

            // Product's Price
            if ($this->getProduct()->price_type_id === ProductPriceType::PER_ITEM) {
                $unitPrice = $this->getProduct()->price;
            } else {
                // Child's Price
                $unitPrice = $child->product->price ?? null;
            }

            if (! is_null($unitPrice)) {
                $bundlePrice += $unitPrice * $child->quantity;
            }
        }

        return $bundlePrice;
    }

    private function setTrialWorkflowUnit(?TrialWorkflowUnit $trialWorkflowUnit): void
    {
        $this->nextTrialWorkflowUnit = $trialWorkflowUnit;
    }

    private function getNextTrialWorkflowUnit(): ?TrialWorkflowUnit
    {
        return $this->nextTrialWorkflowUnit;
    }

    /**
     * Get Trial Workflow Unit price if applicable
     *
     * @return float|null
     */
    private function getTrialWorkflowPrice(): ?float
    {
        $this->setTrialWorkflowUnit(null);

        if (\system_module_control::check(SMC::TRIAL_WORKFLOW)) {
            return null;
        }

        $lineItem             = $this->getLineItem();
        $trialWorkflowLineItem = TrialWorkflowLineItem::readOnly()
            ->where('order_id', $lineItem->id)
            ->where('order_type_id', $lineItem->type_id)
            ->first();

        if ($trialWorkflowLineItem) {
            $nextTrialWorkflowUnit = $trialWorkflowLineItem
                ->unit
                ->workflow
                ->units()
                ->where('step_number', $trialWorkflowLineItem->unit->step_number + 1)
                ->first();

            $this->setTrialWorkflowUnit($nextTrialWorkflowUnit);

            if ($nextTrialWorkflowUnit && $nextTrialWorkflowUnit->isPriceSet()) {
                return $nextTrialWorkflowUnit->price;
            }
        }

        return null;
    }
}
