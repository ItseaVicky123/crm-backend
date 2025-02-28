<?php

namespace App\Lib\Payments\Nmi\Interfaces;

interface NmiRequestProvider extends NmiTransactionTypeProvider
{
    /**
     * @return string|null
     */
    public function getTransactionType(): ?string;

    /**
     * @param string|null $transactionType
     * @return self
     */
    public function setTransactionType(?string $transactionType): self;

    /**
     * @return string|null
     */
    public function getSecurityKey(): ?string;

    /**
     * @param string|null $securityKey
     * @return self
     */
    public function setSecurityKey(?string $securityKey): self;

    /**
     * @return int|null
     */
    public function getDupSeconds(): ?int;

    /**
     * @param int|null $dupSeconds
     * @return self
     */
    public function setDupSeconds(?int $dupSeconds): self;

    /**
     * @return NmiOrderProvider|null
     */
    public function getNmiOrder(): ?NmiOrderProvider;

    /**
     * @param NmiOrderProvider|null $nmiOrder
     * @return self
     */
    public function setNmiOrder(?NmiOrderProvider $nmiOrder): self;

    /**
     * @return BillingInfoProvider|null
     */
    public function getBillingInfo(): ?BillingInfoProvider;

    /**
     * @param BillingInfoProvider|null $billingInfo
     * @return self
     */
    public function setBillingInfo(?BillingInfoProvider $billingInfo): self;

    /**
     * @return ShippingInfoProvider|null
     */
    public function getShippingInfo(): ?ShippingInfoProvider;

    /**
     * @param ShippingInfoProvider|null $shippingInfo
     * @return self
     */
    public function setShippingInfo(?ShippingInfoProvider $shippingInfo): self;

    /**
     * @return DescriptorInfoProvider|null
     */
    public function getDescriptorInfo(): ?DescriptorInfoProvider;

    /**
     * @param DescriptorInfoProvider|null $descriptorInfo
     * @return self
     */
    public function setDescriptorInfo(?DescriptorInfoProvider $descriptorInfo): self;

    /**
     * @return TdsInfoProvider|null
     */
    public function getTdsInfo(): ?TdsInfoProvider;

    /**
     * @param TdsInfoProvider|null $tdsInfo
     * @return self
     */
    public function setTdsInfo(?TdsInfoProvider $tdsInfo): self;

    /**
     * @return VatTaxInfoProvider|null
     */
    public function getVatTaxInfo(): ?VatTaxInfoProvider;

    /**
     * @param VatTaxInfoProvider|null $vatTaxInfo
     * @return self
     */
    public function setVatTaxInfo(?VatTaxInfoProvider $vatTaxInfo): self;

    /**
     * @return RecurringInfoProvider|null
     */
    public function getRecurringInfo(): ?RecurringInfoProvider;

    /**
     * @param RecurringInfoProvider|null $recurringInfo
     * @return self
     */
    public function setRecurringInfo(?RecurringInfoProvider $recurringInfo): self;

    /**
     * @return PaymentMethodProvider|null
     */
    public function getPaymentMethod(): ?PaymentMethodProvider;

    /**
     * @param PaymentMethodProvider|null $paymentMethod
     * @return self
     */
    public function setPaymentMethod(?PaymentMethodProvider $paymentMethod): self;

    /**
     * @return VaultInfoProvider|null
     */
    public function getVaultInfo(): ?VaultInfoProvider;

    /**
     * @param VaultInfoProvider|null $vaultInfo
     * @return self
     */
    public function setVaultInfo(?VaultInfoProvider $vaultInfo): self;

    /**
     * @return MerchantFieldsProvider|null
     */
    public function getMerchantFields(): ?MerchantFieldsProvider;

    /**
     * @param MerchantFieldsProvider|null $merchantFields
     * @return self
     */
    public function setMerchantFields(?MerchantFieldsProvider $merchantFields): self;

    /**
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * @param string|null $username
     * @return self
     */
    public function setUsername(?string $username): self;

    /**
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * @param string|null $password
     * @return self
     */
    public function setPassword(?string $password): self;

    /**
     * @return string|null
     */
    public function isTestMode(): ?string;

    /**
     * @return string|null
     */
    public function getTestMode(): ?string;

    /**
     * @param bool $setKey      True if we should set the Test mode security key also
     * @param bool $setUsername True if we should set the Test mode username also
     * @param bool $setPassword True if we should set the Test mode password also
     * @return self
     */
    public function setTestMode(bool $setKey = true, bool $setUsername = false, bool $setPassword = false): self;

    /**
     * @return NmiResponseProvider
     */
    public function getNmiResponse(): NmiResponseProvider;

    /**
     * @param NmiResponseProvider $nmiResponse
     * @return self
     */
    public function setNmiResponse(NmiResponseProvider $nmiResponse): self;

    /**
     * @return NmiResponseProvider
     */
    public function processSale(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processCredit(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processRefund(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processAuth(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processValidate(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processUpdate(): NmiResponseProvider;

    /**
     * @return NmiResponseProvider
     */
    public function processOfflineSale(): NmiResponseProvider;

    /**
     * Process an NMI transaction
     *
     * @param string $transactionType One of the X_REQUEST constants;
     * @return NmiResponseProvider
     */
    public function process(string $transactionType): NmiResponseProvider;
}