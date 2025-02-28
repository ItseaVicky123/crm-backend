<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\BillingInfoProvider;

class BillingInfo implements BillingInfoProvider
{
    public const BILLING_METHOD_RECURRING   = 'recurring';

    public const BILLING_METHOD_INSTALLMENT = 'installment';

    /**
     * Used in test mode to cause a declined message
     */
    public const TEST_MODE_DECLINE_AMOUNT = '0.45';

    /**
     * Used in test mode to To simulate an AVS match.
     */
    public const TEST_MODE_ADDRESS1 = '888';

    /**
     * Used in test mode to To simulate an AVS match.
     */
    public const TEST_MODE_ZIP = '77777';

    /**
     * Total amount to be charged.
     * Format: x.xx
     * NOTE: For validate, the amount must be omitted or set to 0.00.
     * NOTE: When processing a refund, setting this amount to 0.00 will
     * refund the entire original amount.
     *
     * @var string|null
     */
    protected ?string $amount = null;

    /**
     * The sales tax, included in the transaction amount, associated with the purchase.
     * Setting tax equal to '-1' indicates an order that is exempt from sales tax.
     * Default: '0.00'
     * Format: x.xx
     *
     * @var string|null
     */
    protected ?string $tax = null;

    /**
     *  Values: One of the BILLING_METHOD_X constants
     *
     * @var string|null
     */
    protected ?string $billingMethod = null;

    /**
     * Specify installment billing number, on supported processors.
     * For use when "billingMethod" is set to installment.
     * Values: 0-99
     *
     * @var string|null
     */
    protected ?string $billingNumber = null;

    /**
     * Specify installment billing total on supported processors.
     * For use when "billingMethod" is set to installment.
     *
     * @var string|null
     */
    protected ?string $billingTotal = null;

    /**
     * @var string|null
     */
    protected ?string $phone = null;

    /**
     * @var string|null
     */
    protected ?string $fax = null;

    /**
     * @var string|null
     */
    protected ?string $website = null;

    /**
     * @var string|null
     */
    protected ?string $firstName = null;

    /**
     * @var string|null
     */
    protected ?string $lastName = null;

    /**
     * @var string|null
     */
    protected ?string $company = null;

    /**
     * @var string|null
     */
    protected ?string $address1 = null;

    /**
     * @var string|null
     */
    protected ?string $address2 = null;

    /**
     * @var string|null
     */
    protected ?string $city = null;

    /**
     * Format: CC
     *
     * @var string|null
     */
    protected ?string $state = null;

    /**
     * @var string|null
     */
    protected ?string $zip = null;

    /**
     * Country codes are as shown in ISO 3166. Format: CC
     *
     * @var string|null
     */
    protected ?string $country = null;

    /**
     * @var string|null
     */
    protected ?string $email = null;

    /**
     * IP address of cardholder, this field is recommended.
     * Format: xxx.xxx.xxx.xxx
     *
     * @var string|null
     */
    protected ?string $ipAddress = null;

    /**
     * Customer's social security number, checked against bad check writers database
     * if check verification is enabled.
     *
     * @var string|null
     */
    protected ?string $socialSecurityNumber = null;

    /**
     * @var string|null
     */
    protected ?string $driversLicenseNumber = null;

    /**
     * @var string|null
     */
    protected ?string $driversLicenseDob = null;

    /**
     * Format: CC
     *
     * @var string|null
     */
    protected ?string $driversLicenseState = null;

    /**
     * Cardholder signature image. For use with "sale" and "auth" actions only.
     * Format: base64 encoded raw PNG image. (16kiB maximum)
     *
     * @var string|null
     */
    protected ?string $signatureImage = null;

    /**
     * Amount included in the transaction amount of any discount applied to
     * complete order by the merchant
     * Default: '0.00'
     * Format: x.xx"
     *
     * @var string|null
     */
    protected ?string $discountAmount = null;

    /**
     * The national tax amount included in the transaction amount
     * Default: '0.00'
     * Format: x.xx"
     *
     * @var string|null
     */
    protected ?string $nationalTaxAmount = null;

    /**
     * Second tax amount included in the transaction amount in countries where
     * more than one type of tax can be applied to the purchases
     * Default: '0.00'
     * Format: x.xx"
     *
     * @var string|null
     */
    protected ?string $alternateTaxAmount = null;

    /**
     * Tax identification number of the merchant that reported the alternate tax amount
     *
     * @var string|null
     */
    protected ?string $alternateTaxId = null;

    /**
     * If set to true, when the customer is charged, they will be sent a transaction receipt.
     * Values: true or false
     *
     * @var bool|null
     */
    protected ?bool $customerReceipt = null;
    
    /**
     * @return string|null
     *
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @param string|null $amount
     * @return BillingInfoProvider
     */
    public function setAmount(?string $amount): BillingInfoProvider
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTax(): ?string
    {
        return $this->tax;
    }

    /**
     * @param string|null $tax
     * @return BillingInfoProvider
     */
    public function setTax(?string $tax): BillingInfoProvider
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBillingMethod(): ?string
    {
        return $this->billingMethod;
    }

    /**
     * @param string|null $billingMethod
     * @return BillingInfoProvider
     */
    public function setBillingMethod(?string $billingMethod): BillingInfoProvider
    {
        $this->billingMethod = $billingMethod;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBillingNumber(): ?string
    {
        return $this->billingNumber;
    }

    /**
     * @param string|null $billingNumber
     * @return BillingInfoProvider
     */
    public function setBillingNumber(?string $billingNumber): BillingInfoProvider
    {
        $this->billingNumber = $billingNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBillingTotal(): ?string
    {
        return $this->billingTotal;
    }

    /**
     * @param string|null $billingTotal
     * @return BillingInfoProvider
     */
    public function setBillingTotal(?string $billingTotal): BillingInfoProvider
    {
        $this->billingTotal = $billingTotal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSocialSecurityNumber(): ?string
    {
        return $this->socialSecurityNumber;
    }

    /**
     * @param string|null $socialSecurityNumber
     * @return BillingInfoProvider
     */
    public function setSocialSecurityNumber(?string $socialSecurityNumber): BillingInfoProvider
    {
        $this->socialSecurityNumber = $socialSecurityNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDriversLicenseNumber(): ?string
    {
        return $this->driversLicenseNumber;
    }

    /**
     * @param string|null $driversLicenseNumber
     * @return BillingInfoProvider
     */
    public function setDriversLicenseNumber(?string $driversLicenseNumber): BillingInfoProvider
    {
        $this->driversLicenseNumber = $driversLicenseNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDriversLicenseDob(): ?string
    {
        return $this->driversLicenseDob;
    }

    /**
     * @param string|null $driversLicenseDob
     * @return BillingInfoProvider
     */
    public function setDriversLicenseDob(?string $driversLicenseDob): BillingInfoProvider
    {
        $this->driversLicenseDob = $driversLicenseDob;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDriversLicenseState(): ?string
    {
        return $this->driversLicenseState;
    }

    /**
     * @param string|null $driversLicenseState
     * @return BillingInfoProvider
     */
    public function setDriversLicenseState(?string $driversLicenseState): BillingInfoProvider
    {
        $this->driversLicenseState = $driversLicenseState;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSignatureImage(): ?string
    {
        return $this->signatureImage;
    }

    /**
     * @param string|null $signatureImage
     * @return BillingInfoProvider
     */
    public function setSignatureImage(?string $signatureImage): BillingInfoProvider
    {
        $this->signatureImage = $signatureImage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return BillingInfoProvider
     */
    public function setFirstName(?string $firstName): BillingInfoProvider
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return BillingInfoProvider
     */
    public function setLastName(?string $lastName): BillingInfoProvider
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string|null $company
     * @return BillingInfoProvider
     */
    public function setCompany(?string $company): BillingInfoProvider
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    /**
     * @param string|null $address1
     * @return BillingInfoProvider
     */
    public function setAddress1(?string $address1): BillingInfoProvider
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    /**
     * @param string|null $address2
     * @return BillingInfoProvider
     */
    public function setAddress2(?string $address2): BillingInfoProvider
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return BillingInfoProvider
     */
    public function setCity(?string $city): BillingInfoProvider
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     * @return BillingInfoProvider
     */
    public function setState(?string $state): BillingInfoProvider
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string|null $zip
     * @return BillingInfoProvider
     */
    public function setZip(?string $zip): BillingInfoProvider
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return BillingInfoProvider
     */
    public function setCountry(?string $country): BillingInfoProvider
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return BillingInfoProvider
     */
    public function setEmail(?string $email): BillingInfoProvider
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @param string|null $ipAddress
     * @return BillingInfoProvider
     */
    public function setIpAddress(?string $ipAddress): BillingInfoProvider
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return BillingInfoProvider
     */
    public function setPhone(?string $phone): BillingInfoProvider
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFax(): ?string
    {
        return $this->fax;
    }

    /**
     * @param string|null $fax
     * @return BillingInfoProvider
     */
    public function setFax(?string $fax): BillingInfoProvider
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     * @return BillingInfoProvider
     */
    public function setWebsite(?string $website): BillingInfoProvider
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    /**
     * @param string|null $discountAmount
     * @return BillingInfoProvider
     */
    public function setDiscountAmount(?string $discountAmount): BillingInfoProvider
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNationalTaxAmount(): ?string
    {
        return $this->nationalTaxAmount;
    }

    /**
     * @param string|null $nationalTaxAmount
     * @return BillingInfoProvider
     */
    public function setNationalTaxAmount(?string $nationalTaxAmount): BillingInfoProvider
    {
        $this->nationalTaxAmount = $nationalTaxAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlternateTaxAmount(): ?string
    {
        return $this->alternateTaxAmount;
    }

    /**
     * @param string|null $alternateTaxAmount
     * @return BillingInfoProvider
     */
    public function setAlternateTaxAmount(?string $alternateTaxAmount): BillingInfoProvider
    {
        $this->alternateTaxAmount = $alternateTaxAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlternateTaxId(): ?string
    {
        return $this->alternateTaxId;
    }

    /**
     * @param string|null $alternateTaxId
     * @return BillingInfoProvider
     */
    public function setAlternateTaxId(?string $alternateTaxId): BillingInfoProvider
    {
        $this->alternateTaxId = $alternateTaxId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerReceipt(): ?string
    {
        return $this->customerReceipt;
    }

    /**
     * @param bool|null $customerReceipt
     * @return BillingInfoProvider
     */
    public function setCustomerReceipt(?bool $customerReceipt): BillingInfoProvider
    {
        $this->customerReceipt = $customerReceipt;

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
            'amount'                 => $this->amount,
            'tax'                    => $this->tax,
            'billing_method'         => $this->billingMethod,
            'billing_number'         => $this->billingNumber,
            'billing_total'          => $this->billingTotal,
            'phone'                  => $this->phone,
            'fax'                    => $this->fax,
            'website'                => $this->website,
            'firstname'              => $this->firstName,
            'lastname'               => $this->lastName,
            'company'                => $this->company,
            'address1'               => $this->address1,
            'address2'               => $this->address2,
            'city'                   => $this->city,
            'state'                  => $this->state,
            'zip'                    => $this->zip,
            'country'                => $this->country,
            'email'                  => $this->email,
            'ipaddress'              => $this->ipAddress,
            'social_security_number' => $this->socialSecurityNumber,
            'drivers_license_number' => $this->driversLicenseNumber,
            'drivers_license_dob'    => $this->driversLicenseDob,
            'drivers_license_state'  => $this->driversLicenseState,
            'signature_image'        => $this->signatureImage,
            'discount_amount'        => $this->discountAmount,
            'national_tax_amount'    => $this->nationalTaxAmount,
            'alternate_tax_amount'   => $this->alternateTaxAmount,
            'alternate_tax_id'       => $this->alternateTaxId,
            'customer_receipt'       => $this->customerReceipt ? 'true' : 'false',
        ];
    }
}