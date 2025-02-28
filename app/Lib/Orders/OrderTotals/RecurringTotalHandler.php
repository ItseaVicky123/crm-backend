<?php

namespace App\Lib\Orders\OrderTotals;

use App\Lib\Orders\OrderTotals\ModuleRequests\RecurringTotalRequest;
use App\Models\OrderProductBundle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Lib\Orders\NextRecurringOrderPriceCalculator;
use App\Lib\Orders\NextLineItem;

/**
 * Class RecurringTotalHandler
 *
 * @package App\Lib\Orders\OrderTotals
 */
class RecurringTotalHandler
{
    /**
     * @var RecurringTotalRequest $request
     */
    protected RecurringTotalRequest $request;

    /**
     * @var int|null $orderId
     */
    protected ?int $orderId = null;

    /**
     * @var string|null recurringDate
     */
    protected ?string $recurringDate = null;

    /**
     * @var bool calculateTax
     */
    protected bool $calculateTax = false;

    /**
     * @var bool calculateShipping
     */
    protected bool $calculateShipping = true;

    /**
     * RecurringTotalHandler constructor.
     *
     * @param RecurringTotalRequest $request
     */

    public function __construct(RecurringTotalRequest $request)
    {
        $this->request           = $request;
        $this->orderId           = $this->request->get('order_id');
        $this->recurringDate     = $this->request->get('recurring_date');
        $this->calculateTax      = $this->request->get('calculate_tax', $this->calculateTax);
        $this->calculateShipping = $this->request->get('calculate_shipping', $this->calculateShipping);
    }

    /**
     * Calculate the recurring order total.
     *
     * @return Array
     */
    public function recurring_order_calculate(): array
    {
        $result = [];

        if (! empty($this->recurringDate)) {
            $calculators = collect([new NextRecurringOrderPriceCalculator($this->orderId, $this->recurringDate)]);
        } else {
            $calculators = NextRecurringOrderPriceCalculator::createCalculators($this->orderId);
        }

        foreach ($calculators as $calculator) {
            // Calculate revenue
            $calculator
                ->setShouldCalculateTaxes($this->calculateTax)
                ->setShouldCalculateShipping($this->calculateShipping)
                ->calculate();

            if ($calculator->getNextLineItems()->isEmpty()) {
                continue;
            }

            $result[$calculator->getRecurringDate()] = $this->buildOrderArray($calculator);
        }

        return $result;
    }

    /**
     * Build the order array from the calculator.
     *
     * @param NextRecurringOrderPriceCalculator $calculator
     * @return array
     */
    private function buildOrderArray(NextRecurringOrderPriceCalculator $calculator): array
    {
        $orderInfo = [
            'total'           => $calculator->getTotalAmount(),
            'subtotal'        => $calculator->getSubTotalAmount(),
            'discount_amount' => $calculator->getDiscountAmount(),
            'discounts'       => $calculator->getDiscounts()
        ];

        if ($this->calculateTax) {
            $orderInfo['tax'] = [
                'sales_tax_amount'  => $calculator->getTaxAmount(),
                'sales_tax_percent' => $calculator->getSalesTaxPercentage(),
                'vat_tax_amount'    => $calculator->getVatTaxAmount(),
                'vat_tax_percent'   => $calculator->getVatTaxRate(),
            ];
        }

        if ($this->calculateShipping) {
            $orderInfo['shipping'] = [
                'shipping_total_amount' => $calculator->getShippingTotalAmount(),
                'shipping_tax_amount'   => $calculator->getShippingTaxAmount(),
                'shipping_tax_percent'  => $calculator->getShippingTaxPercentage(),
                'shipping_discount'     => $calculator->getShippingDiscountAmount(),
            ];
        }

        $orderInfo['line_items'] = $this->buildLineItemArray($calculator->getNextLineItems());

        return $orderInfo;
    }

    /**
     * Build the line item array from the next line items.
     *
     * @param Collection $nextLineItems
     * @return Collection
     */
    private function buildLineItemArray(Collection $nextLineItems): Collection
    {
        return $nextLineItems->map(function (NextLineItem $nextLineItem) {
            $product = $nextLineItem->getProduct();

            $lineItems = [
                'product_id'      => $nextLineItem->getProductId(),
                'product_name'    => $product->name,
                'unit_price'      => $nextLineItem->getUnitPrice(),
                'qty'             => $nextLineItem->getQuantity(),
                'subtotal'        => $nextLineItem->getSubtotal(),
                'total'           => $nextLineItem->getTotal(),
                'discount_amount' => $nextLineItem->getDiscountAmount(),
                'discounts'       => $nextLineItem->getDiscounts(),
                'variant_id'      => $nextLineItem->getVariantId(),
                'is_variant'      => $nextLineItem->isVariant(),
                'is_shippable'    => $product->is_shippable,
                'is_taxable'      => $product->is_taxable,
                'is_bundle'       => $nextLineItem->isBundle(),
                'subscription_id' => $nextLineItem->getLineItem()->subscription_id,
            ];

            if ($this->calculateTax) {
                $lineItems['tax'] = [
                    'tax_amount'     => $nextLineItem->getTaxAmount(),
                    'tax_rate'       => $nextLineItem->getTaxRate(),
                    'vat_tax_amount' => $nextLineItem->getVatTaxAmount(),
                    'vat_tax_rate'   => $nextLineItem->getVatTaxRate(),
                ];
            }

            if($nextLineItem->isBundle()) {
                $lineItems['bundle_children'] = $this->bindBundleChildrenArray($nextLineItem->getChildren());
            }

            return $lineItems;
        });
    }

    /**
     * Build the bundle children array from the children collection of next line item.
     *
     * @param Collection $children
     * @return Collection
     */
    private function bindBundleChildrenArray(Collection $children): Collection
    {
        return $children->map(function (OrderProductBundle $child) {
            return [
                'name' => $child->product->name,
                'qty'  => $child->quantity,
            ];
        });
    }
}
