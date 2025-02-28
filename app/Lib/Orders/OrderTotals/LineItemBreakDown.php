<?php

namespace App\Lib\Orders\OrderTotals;

use App\Models\OrderProductBundle;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscription;
use Illuminate\Support\Collection;

/**
 * @package App\Lib\Orders\OrderTotals
 */
class LineItemBreakDown
{
    private ?Subscription   $lineItem;
    private ?Product        $product  = null;
    private ?ProductVariant $variant  = null;
    private ?Collection     $children = null;

    /* @var float */
    private float $discountAmount = 0.0;
    private float $totalAmount    = 0.0;
    private float $baseUnitPrice  = 0.0;

    /* @var int */
    private int  $quantity      = 0;
    private ?int $prepaidCycles = 1;
    private ?int $productId     = null;
    private ?int $variantId     = null;

    /* @var array */
    protected array $discounts = [];

    /**
     * @param \App\Models\Subscription $lineItem
     */
    public function __construct(Subscription $lineItem)
    {
        $this->lineItem = $lineItem;

        $this
            ->fetchProduct()
            ->fetchQuantity()
            ->fetchUnitPrice();

        if ($lineItem->order_subscription->is_prepaid ?? false) {
            $this->setPrepaidCycles($lineItem->order_subscription->prepaid_cycles ?? 1);
        }
    }

    /**
     * Check if the line item is a Bundle
     *
     * @return bool
     */
    public function isBundle(): bool
    {
        return $this->getProduct()->is_bundle;
    }

    /**
     * Get bundle children collection
     *
     * @return \Illuminate\Support\Collection default empty Collection
     */
    public function getChildren(): Collection
    {
        if (! $this->children && $this->isBundle()) {
            $this->children = OrderProductBundle::readOnly()
                ->where([
                    'order_id'      => $this->getLineItem()->order_id,
                    'bundle_id'     => $this->getProductId(),
                    'main_flag'     => $this->getLineItem()->isMain(),
                    'is_next_cycle' => 0,
                ])->get();
        }

        return $this->children ?? $this->children = collect();
    }

    /**
     * Record discount on a lani item level
     *
     * @param string $name
     * @param float $discountAmount
     * @return void
     */
    public function addDiscount(string $name, float $discountAmount): void
    {
        $this->discounts[$name] = $discountAmount;
    }

    /**
     * @return float
     */
    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    /**
     * Get discount amount by the name
     *
     * @param string $name
     * @return float default $0
     */
    public function getDiscount(string $name): float
    {
        return $this->getDiscounts()[$name] ?? 0;
    }

    /**
     * @return array
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    /**
     * Get line item subtotal price. which is unit price multiplied by quantity
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return bcmul(
            $this->getBaseUnitPrice(),
            // Multiply line item quantity by prepaid cycltes to get total prepaid quantities
            $this->getQuantity() * $this->getPrepaidCycles(),
            4
        );
    }

    /**
     * Get Order or Upsell Model
     *
     * @return \App\Models\Subscription
     */
    public function getLineItem(): Subscription
    {
        return $this->lineItem;
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->totalAmount;
    }

    /**
     * @return float
     */
    public function getBaseUnitPrice(): float
    {
        return $this->baseUnitPrice;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Check if the line item is a variant
     *
     * @return bool
     */
    public function isVariant(): bool
    {
        return (bool) $this->variantId;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return \App\Models\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \App\Models\ProductVariant|null
     */
    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    /**
     * @return int|null
     */
    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    /**
     * @param int $productId
     * @return void
     */
    private function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @param \App\Models\Product $product
     * @return void
     */
    private function setProduct(Product $product): void
    {
        $this->product = $product;

        $this->setProductId($product->id);
    }

    /**
     * @param int|null $variantId
     * @return void
     */
    private function setVariantId(?int $variantId): void
    {
        $this->variantId = $variantId;
    }

    /**
     * @param \App\Models\ProductVariant|null $variant
     * @return void
     */
    private function setVariant(?ProductVariant $variant): void
    {
        $this->variant = $variant;

        $this->setVariantId($variant->id ?? null);
    }

    /**
     * @param int $quantity
     * @return void
     */
    private function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @param float|null $baseUnitPrice
     * @return void
     */
    public function setBaseUnitPrice(?float $baseUnitPrice): void
    {
        $this->baseUnitPrice = $baseUnitPrice ?? 0.0;
    }

    /**
     * Fetch product and variant
     *
     * @return $this
     */
    private function fetchProduct(): self
    {
        $orderProduct = $this->getLineItem()->order_product;

        $this->setProduct($orderProduct->product);
        $this->setVariant($orderProduct->variant);

        return $this;
    }

    /**
     * Fetch quantity
     *
     * @return self
     */
    private function fetchQuantity(): self
    {
        $this->setQuantity($this->getLineItem()->order_product->quantity ?: 1);

        return $this;
    }

    /**
     * @return $this
     */
    public function fetchTotalAmount(): self
    {
        $this->totalAmount = $this->getSubtotal() - $this->fetchDiscountAmount()->getDiscountAmount();
        $this->totalAmount = round(max(0, $this->totalAmount), 2);

        return $this;
    }

    /**
     * Fetch total discount amount for this line item
     *
     * @return self
     */
    private function fetchDiscountAmount(): self
    {
        $this->discountAmount = 0.0;

        foreach ($this->getDiscounts() as $discount) {
            $this->discountAmount += $discount;
        }

        return $this;
    }

    /**
     * Fetch Unit price based on price after discounts applied divided by quantity.
     * We will restore unit price afterwards if possible, while discounts applied
     *
     * @return void
     */
    private function fetchUnitPrice(): void
    {
        // Price after all discounts applied
        $this->setBaseUnitPrice(bcdiv(
            $this->getLineItem()->subtotal->value ?? 0,
            $this->getQuantity(),
            4
        ));
    }

    /**
     * @return bool
     */
    public function isPrepaid(): bool
    {
        return $this->getPrepaidCycles() > 1;
    }

    /**
     * @return int
     */
    public function getPrepaidCycles(): int
    {
        return $this->prepaidCycles;
    }

    /**
     * @param int $prepaidCycles
     * @return $this
     */
    public function setPrepaidCycles(int $prepaidCycles): self
    {
        $this->prepaidCycles = max($prepaidCycles, 1);

        return $this;
    }
}
