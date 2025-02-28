<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\NmiOrderItemProvider;

class NmiOrderItem implements NmiOrderItemProvider
{

    /**
     * Merchant defined description code of the item being purchased
     * @var string|null
     */
    protected ?string $itemProductCode = null;

    /**
     * Description of the item(s) being supplied
     * @var string|null
     */
    protected ?string $itemDescription = null;

    /**
     * International description code of the individual good or service being supplied.
     * The acquirer or processor will provide a list of current codes.
     * @var string|null
     */
    protected ?string $itemCommodityCode = null;

    /**
     * Code for units of measurement as used in international trade
     * Default: 'EACH'
     * @var string|null
     */
    protected ?string $itemUnitOfMeasure = null;

    /**
     * Unit cost of item purchased, may contain up to 4 decimal places
     * @var string|null
     */
    protected ?string $itemUnitCost = null;

    /**
     * Quantity of the item(s) being purchased
     * Default: 1
     * @var int|null
     */
    protected ?int $itemQuantity = null;

    /**
     * Purchase amount associated with the item.
     * Defaults to: 'itemUnitCost' x 'itemQuantity' rounded to the nearest penny
     * Format x.xx
     * @var string|null
     */
    protected ?string $itemTotalAmount = null;

    /**
     * Amount of tax on specific item, amount should not be included in 'total_amount'
     * Default: '0.00'
     * Format x.xx
     * @var string|null
     */
    protected ?string $itemTaxAmount = null;

    /**
     * Percentage representing the value-added tax applied
     * Default: '0.00'
     * Format x.xx
     * @var string|null
     */
    protected ?string $itemTaxRate = null;

    /**
     * Discount amount which can have been applied by the merchant on the sale of the specific item.
     * Amount should not be included in 'total_amount'
     * Default: '0.00'
     * Format x.xx
     * @var string|null
     */
    protected ?string $itemDiscountAmount = null;

    /**
     * Discount rate for the line item. 1% = 1.00
     * Default: '0.00'
     * Format x.xx
     * @var string|null
     */
    protected ?string $itemDiscountRate = null;

    /**
     * Type of value-added taxes that are being used
     * @var string|null
     */
    protected ?string $itemTaxType = null;

    /**
     * Tax identification number of the merchant that reported the alternate tax amount
     * @var string|null
     */
    protected ?string $itemAlternateTaxId = null;

    /**
     * The index of this item in the order's array of items
     * @var int|null
     */
    protected ?int $index = null;

    /**
     * @return string|null
     */
    public function getItemProductCode(): ?string
    {
        return $this->itemProductCode;
    }

    /**
     * @param string|null $itemProductCode
     * @return NmiOrderItemProvider
     */
    public function setItemProductCode(?string $itemProductCode): NmiOrderItemProvider
    {
        $this->itemProductCode = $itemProductCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemDescription(): ?string
    {
        return $this->itemDescription;
    }

    /**
     * @param string|null $itemDescription
     * @return NmiOrderItemProvider
     */
    public function setItemDescription(?string $itemDescription): NmiOrderItemProvider
    {
        $this->itemDescription = $itemDescription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemCommodityCode(): ?string
    {
        return $this->itemCommodityCode;
    }

    /**
     * @param string|null $itemCommodityCode
     * @return NmiOrderItemProvider
     */
    public function setItemCommodityCode(?string $itemCommodityCode): NmiOrderItemProvider
    {
        $this->itemCommodityCode = $itemCommodityCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemUnitOfMeasure(): ?string
    {
        return $this->itemUnitOfMeasure;
    }

    /**
     * @param string|null $itemUnitOfMeasure
     * @return NmiOrderItemProvider
     */
    public function setItemUnitOfMeasure(?string $itemUnitOfMeasure): NmiOrderItemProvider
    {
        $this->itemUnitOfMeasure = $itemUnitOfMeasure;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemUnitCost(): ?string
    {
        return $this->itemUnitCost;
    }

    /**
     * @param string|null $itemUnitCost
     * @return NmiOrderItemProvider
     */
    public function setItemUnitCost(?string $itemUnitCost): NmiOrderItemProvider
    {
        $this->itemUnitCost = $itemUnitCost;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getItemQuantity(): ?int
    {
        return $this->itemQuantity;
    }

    /**
     * @param int|null $itemQuantity
     * @return NmiOrderItemProvider
     */
    public function setItemQuantity(?int $itemQuantity): NmiOrderItemProvider
    {
        $this->itemQuantity = $itemQuantity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemTotalAmount(): ?string
    {
        return $this->itemTotalAmount;
    }

    /**
     * @param string|null $itemTotalAmount
     * @return NmiOrderItemProvider
     */
    public function setItemTotalAmount(?string $itemTotalAmount): NmiOrderItemProvider
    {
        $this->itemTotalAmount = $itemTotalAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemTaxAmount(): ?string
    {
        return $this->itemTaxAmount;
    }

    /**
     * @param string|null $itemTaxAmount
     * @return NmiOrderItemProvider
     */
    public function setItemTaxAmount(?string $itemTaxAmount): NmiOrderItemProvider
    {
        $this->itemTaxAmount = $itemTaxAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemTaxRate(): ?string
    {
        return $this->itemTaxRate;
    }

    /**
     * @param string|null $itemTaxRate
     * @return NmiOrderItemProvider
     */
    public function setItemTaxRate(?string $itemTaxRate): NmiOrderItemProvider
    {
        $this->itemTaxRate = $itemTaxRate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemDiscountAmount(): ?string
    {
        return $this->itemDiscountAmount;
    }

    /**
     * @param string|null $itemDiscountAmount
     * @return NmiOrderItemProvider
     */
    public function setItemDiscountAmount(?string $itemDiscountAmount): NmiOrderItemProvider
    {
        $this->itemDiscountAmount = $itemDiscountAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemDiscountRate(): ?string
    {
        return $this->itemDiscountRate;
    }

    /**
     * @param string|null $itemDiscountRate
     * @return NmiOrderItemProvider
     */
    public function setItemDiscountRate(?string $itemDiscountRate): NmiOrderItemProvider
    {
        $this->itemDiscountRate = $itemDiscountRate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemTaxType(): ?string
    {
        return $this->itemTaxType;
    }

    /**
     * @param string|null $itemTaxType
     * @return NmiOrderItemProvider
     */
    public function setItemTaxType(?string $itemTaxType): NmiOrderItemProvider
    {
        $this->itemTaxType = $itemTaxType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemAlternateTaxId(): ?string
    {
        return $this->itemAlternateTaxId;
    }

    /**
     * @param string|null $itemAlternateTaxId
     * @return NmiOrderItemProvider
     */
    public function setItemAlternateTaxId(?string $itemAlternateTaxId): NmiOrderItemProvider
    {
        $this->itemAlternateTaxId = $itemAlternateTaxId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIndex(): ?int
    {
        return $this->index;
    }

    /**
     * @param int|null $index
     * @return NmiOrderItemProvider
     */
    public function setIndex(?int $index): NmiOrderItemProvider
    {
        $this->index = $index;
        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            "item_product_code_{$this->index}"     => $this->itemProductCode,
            "item_description_{$this->index}"       => $this->itemDescription,
            "item_commodity_code_{$this->index}"   => $this->itemCommodityCode,
            "item_unit_of_measure_{$this->index}"  => $this->itemUnitOfMeasure,
            "item_unit_cost_{$this->index}"        => $this->itemUnitCost,
            "item_quantity_{$this->index}"         => $this->itemQuantity,
            "item_total_amount_{$this->index}"     => $this->itemTotalAmount,
            "item_tax_amount_{$this->index}"       => $this->itemTaxAmount,
            "item_tax_rate_{$this->index}"         => $this->itemTaxRate,
            "item_discount_amount_{$this->index}"  => $this->itemDiscountAmount,
            "item_discount_rate_{$this->index}"    => $this->itemDiscountRate,
            "item_tax_type_{$this->index}"         => $this->itemTaxType,
            "item_alternate_tax_id_{$this->index}" => $this->itemAlternateTaxId,
        ];
    }
}
