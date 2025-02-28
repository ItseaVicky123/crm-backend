<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\VatTaxInfoProvider;

class VatTaxInfo implements VatTaxInfoProvider
{

    /**
     * Contains the amount of any value added taxes which can be associated with the purchased item
     * Default: '0.00'
     * Format: x.xx
     * @var string|null
     */
    protected ?string $vatTaxAmount = null;

    /**
     * Contains the tax rate used to calculate the sales tax amount appearing.
     * Can contain up to 2 decimal places, e.g. 1% = 1.00
     * Default: '0.00'
     * Format: x.xx
     * @var string|null
     */
    protected ?string $vatTaxRate = null;

    /**
     * Value added tax registration number supplied by the cardholder.
     * @var string|null
     */
    protected ?string $vatInvoiceReferenceNumber = null;

    /**
     * Government assigned tax identification number of the merchant for
     * whom the goods or services were purchased from
     * @var string|null
     */
    protected ?string $merchantVatRegistration = null;

    /**
     * @return string|null
     */
    public function getVatTaxAmount(): ?string
    {
        return $this->vatTaxAmount;
    }

    /**
     * @param string|null $vatTaxAmount
     * @return VatTaxInfoProvider
     */
    public function setVatTaxAmount(?string $vatTaxAmount): VatTaxInfoProvider
    {
        $this->vatTaxAmount = $vatTaxAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVatTaxRate(): ?string
    {
        return $this->vatTaxRate;
    }

    /**
     * @param string|null $vatTaxRate
     * @return VatTaxInfoProvider
     */
    public function setVatTaxRate(?string $vatTaxRate): VatTaxInfoProvider
    {
        $this->vatTaxRate = $vatTaxRate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVatInvoiceReferenceNumber(): ?string
    {
        return $this->vatInvoiceReferenceNumber;
    }

    /**
     * @param string|null $vatInvoiceReferenceNumber
     * @return VatTaxInfoProvider
     */
    public function setVatInvoiceReferenceNumber(?string $vatInvoiceReferenceNumber): VatTaxInfoProvider
    {
        $this->vatInvoiceReferenceNumber = $vatInvoiceReferenceNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMerchantVatRegistration(): ?string
    {
        return $this->merchantVatRegistration;
    }

    /**
     * @param string|null $merchantVatRegistration
     * @return VatTaxInfoProvider
     */
    public function setMerchantVatRegistration(?string $merchantVatRegistration): VatTaxInfoProvider
    {
        $this->merchantVatRegistration = $merchantVatRegistration;

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
            'vat_tax_amount'               => $this->vatTaxAmount,
            'vat_tax_rate'                 => $this->vatTaxRate,
            'vat_invoice_reference_number' => $this->vatInvoiceReferenceNumber,
            'merchant_vat_registration'    => $this->merchantVatRegistration,
        ];
    }
}
