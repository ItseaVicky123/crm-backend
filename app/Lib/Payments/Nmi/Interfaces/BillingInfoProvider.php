<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface BillingInfoProvider extends Arrayable
{

    /**
     * @return string|null
     *
     */
    public function getAmount(): ?string;

    /**
     * @param string|null $amount
     * @return self
     */
    public function setAmount(?string $amount): self;

    /**
     * @return string|null
     */
    public function getTax(): ?string;

    /**
     * @param string|null $tax
     * @return self
     */
    public function setTax(?string $tax): self;

    /**
     * @return string|null
     */
    public  function getBillingMethod(): ?string;

    /**
     * @param string|null $billingMethod
     * @return self
     */
    public  function setBillingMethod(?string $billingMethod): self;

    /**
     * @return string|null
     */
    public  function getBillingNumber(): ?string;

    /**
     * @param string|null $billingNumber
     * @return self
     */
    public  function setBillingNumber(?string $billingNumber): self;

    /**
     * @return string|null
     */
    public  function getBillingTotal(): ?string;

    /**
     * @param string|null $billingTotal
     * @return self
     */
    public  function setBillingTotal(?string $billingTotal): self;

    /**
     * @return string|null
     */
    public  function getSocialSecurityNumber(): ?string;

    /**
     * @param string|null $socialSecurityNumber
     * @return self
     */
    public  function setSocialSecurityNumber(?string $socialSecurityNumber): self;

    /**
     * @return string|null
     */
    public  function getDriversLicenseNumber(): ?string;

    /**
     * @param string|null $driversLicenseNumber
     * @return self
     */
    public  function setDriversLicenseNumber(?string $driversLicenseNumber): self;

    /**
     * @return string|null
     */
    public  function getDriversLicenseDob(): ?string;

    /**
     * @param string|null $driversLicenseDob
     * @return self
     */
    public  function setDriversLicenseDob(?string $driversLicenseDob): self;

    /**
     * @return string|null
     */
    public  function getDriversLicenseState(): ?string;

    /**
     * @param string|null $driversLicenseState
     * @return self
     */
    public  function setDriversLicenseState(?string $driversLicenseState): self;

    /**
     * @return string|null
     */
    public  function getSignatureImage(): ?string;

    /**
     * @param string|null $signatureImage
     * @return self
     */
    public  function setSignatureImage(?string $signatureImage): self;

    /**
     * @return string|null
     */
    public  function getFirstName(): ?string;

    /**
     * @param string|null $firstName
     * @return self
     */
    public  function setFirstName(?string $firstName): self;

    /**
     * @return string|null
     */
    public  function getLastName(): ?string;

    /**
     * @param string|null $lastName
     * @return self
     */
    public  function setLastName(?string $lastName): self;

    /**
     * @return string|null
     */
    public  function getCompany(): ?string;

    /**
     * @param string|null $company
     * @return self
     */
    public  function setCompany(?string $company): self;

    /**
     * @return string|null
     */
    public  function getAddress1(): ?string;

    /**
     * @param string|null $address1
     * @return self
     */
    public  function setAddress1(?string $address1): self;

    /**
     * @return string|null
     */
    public  function getAddress2(): ?string;

    /**
     * @param string|null $address2
     * @return self
     */
    public  function setAddress2(?string $address2): self;

    /**
     * @return string|null
     */
    public  function getCity(): ?string;

    /**
     * @param string|null $city
     * @return self
     */
    public  function setCity(?string $city): self;

    /**
     * @return string|null
     */
    public  function getState(): ?string;

    /**
     * @param string|null $state
     * @return self
     */
    public  function setState(?string $state): self;

    /**
     * @return string|null
     */
    public  function getZip(): ?string;

    /**
     * @param string|null $zip
     * @return self
     */
    public  function setZip(?string $zip): self;

    /**
     * @return string|null
     */
    public  function getCountry(): ?string;

    /**
     * @param string|null $country
     * @return self
     */
    public  function setCountry(?string $country): self;

    /**
     * @return string|null
     */
    public  function getEmail(): ?string;

    /**
     * @param string|null $email
     * @return self
     */
    public  function setEmail(?string $email): self;

    /**
     * @return string|null
     */
    public  function getIpAddress(): ?string;

    /**
     * @param string|null $ipaddress
     * @return self
     */
    public  function setIpAddress(?string $ipaddress): self;

    /**
     * @return string|null
     */
    public  function getPhone(): ?string;

    /**
     * @param string|null $phone
     * @return self
     */
    public  function setPhone(?string $phone): self;

    /**
     * @return string|null
     */
    public  function getFax(): ?string;

    /**
     * @param string|null $fax
     * @return self
     */
    public  function setFax(?string $fax): self;

    /**
     * @return string|null
     */
    public  function getWebsite(): ?string;

    /**
     * @param string|null $website
     * @return self
     */
    public  function setWebsite(?string $website): self;


    /**
     * @return string|null
     */
    public  function getDiscountAmount(): ?string;

    /**
     * @param string|null $discountAmount
     * @return self
     */
    public  function setDiscountAmount(?string $discountAmount): self;

    /**
     * @return string|null
     */
    public  function getNationalTaxAmount(): ?string;

    /**
     * @param string|null $nationalTaxAmount
     * @return self
     */
    public  function setNationalTaxAmount(?string $nationalTaxAmount): self;

    /**
     * @return string|null
     */
    public  function getAlternateTaxAmount(): ?string;

    /**
     * @param string|null $alternateTaxAmount
     * @return self
     */
    public  function setAlternateTaxAmount(?string $alternateTaxAmount): self;

    /**
     * @return string|null
     */
    public  function getAlternateTaxId(): ?string;

    /**
     * @param string|null $alternateTaxId
     * @return self
     */
    public  function setAlternateTaxId(?string $alternateTaxId): self;

    /**
     * @return string|null
     */
    public  function getCustomerReceipt(): ?string;

    /**
     * @param bool|null $customerReceipt
     * @return self
     */
    public  function setCustomerReceipt(?bool $customerReceipt): self;

}