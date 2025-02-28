<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface NmiOrderItemProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getItemProductCode(): ?string;

    /**
     * @param string|null $itemProductCode
     * @return self
     */
    public  function setItemProductCode(?string $itemProductCode): self;

    /**
     * @return string|null
     */
    public  function getItemDescription(): ?string;

    /**
     * @param string|null $itemDescription
     * @return self
     */
    public  function setItemDescription(?string $itemDescription): self;

    /**
     * @return string|null
     */
    public  function getItemCommodityCode(): ?string;

    /**
     * @param string|null $itemCommodityCode
     * @return self
     */
    public  function setItemCommodityCode(?string $itemCommodityCode): self;

    /**
     * @return string|null
     */
    public  function getItemUnitOfMeasure(): ?string;

    /**
     * @param string|null $itemUnitOfMeasure
     * @return self
     */
    public  function setItemUnitOfMeasure(?string $itemUnitOfMeasure): self;

    /**
     * @return string|null
     */
    public  function getItemUnitCost(): ?string;

    /**
     * @param string|null $itemUnitCost
     * @return self
     */
    public  function setItemUnitCost(?string $itemUnitCost): self;

    /**
     * @return int|null
     */
    public  function getItemQuantity(): ?int;

    /**
     * @param int|null $itemQuantity
     * @return self
     */
    public  function setItemQuantity(?int $itemQuantity): self;

    /**
     * @return string|null
     */
    public  function getItemTotalAmount(): ?string;

    /**
     * @param string|null $itemTotalAmount
     * @return self
     */
    public  function setItemTotalAmount(?string $itemTotalAmount): self;

    /**
     * @return string|null
     */
    public  function getItemTaxAmount(): ?string;

    /**
     * @param string|null $itemTaxAmount
     * @return self
     */
    public  function setItemTaxAmount(?string $itemTaxAmount): self;

    /**
     * @return string|null
     */
    public  function getItemTaxRate(): ?string;

    /**
     * @param string|null $itemTaxRate
     * @return self
     */
    public  function setItemTaxRate(?string $itemTaxRate): self;

    /**
     * @return string|null
     */
    public  function getItemDiscountAmount(): ?string;

    /**
     * @param string|null $itemDiscountAmount
     * @return self
     */
    public  function setItemDiscountAmount(?string $itemDiscountAmount): self;

    /**
     * @return string|null
     */
    public  function getItemDiscountRate(): ?string;

    /**
     * @param string|null $itemDiscountRate
     * @return self
     */
    public  function setItemDiscountRate(?string $itemDiscountRate): self;

    /**
     * @return string|null
     */
    public  function getItemTaxType(): ?string;

    /**
     * @param string|null $itemTaxType
     * @return self
     */
    public  function setItemTaxType(?string $itemTaxType): self;

    /**
     * @return string|null
     */
    public  function getItemAlternateTaxId(): ?string;

    /**
     * @param string|null $itemAlternateTaxId
     * @return self
     */
    public  function setItemAlternateTaxId(?string $itemAlternateTaxId): self;

    /**
     * @return int|null
     */
    public  function getIndex(): ?int;

    /**
     * @param int|null $index
     * @return self
     */
    public function setIndex(?int $index): self;

}