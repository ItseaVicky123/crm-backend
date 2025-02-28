<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface VatTaxInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getVatTaxAmount(): ?string;

    /**
     * @param string|null $vatTaxAmount
     * @return self
     */
    public  function setVatTaxAmount(?string $vatTaxAmount): self;

    /**
     * @return string|null
     */
    public  function getVatTaxRate(): ?string;

    /**
     * @param string|null $vatTaxRate
     * @return self
     */
    public  function setVatTaxRate(?string $vatTaxRate): self;

    /**
     * @return string|null
     */
    public  function getVatInvoiceReferenceNumber(): ?string;

    /**
     * @param string|null $vatInvoiceReferenceNumber
     * @return self
     */
    public  function setVatInvoiceReferenceNumber(?string $vatInvoiceReferenceNumber): self;

    /**
     * @return string|null
     */
    public  function getMerchantVatRegistration(): ?string;

    /**
     * @param string|null $merchantVatRegistration
     * @return self
     */
    public  function setMerchantVatRegistration(?string $merchantVatRegistration): self;

}
