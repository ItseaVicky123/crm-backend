<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface PaymentMethodProvider extends Arrayable
{
    /**
     * @return int|null
     */
    public function getCvv(): ?int;

    /**
     * @param int|null $cvv
     * @return self
     */
    public function setCvv(?int $cvv): self;

    /**
     * @return string|null
     */
    public function getCheckName(): ?string;

    /**
     * @param string|null $checkName
     * @return self
     */
    public function setCheckName(?string $checkName): self;

    /**
     * @return string|null
     */
    public function getCheckAba(): ?string;

    /**
     * @param string|null $checkAba
     * @return self
     */
    public function setCheckAba(?string $checkAba): self;

    /**
     * @return string|null
     */
    public function getCheckAccount(): ?string;

    /**
     * @param string|null $checkAccount
     * @return self
     */
    public function setCheckAccount(?string $checkAccount): self;

    /**
     * @return string|null
     */
    public function getAccountHolderType(): ?string;

    /**
     * @param string|null $accountHolderType
     * @return self
     */
    public function setAccountHolderType(?string $accountHolderType): self;

    /**
     * @return string|null
     */
    public function getAccountType(): ?string;

    /**
     * @param string|null $accountType
     * @return self
     */
    public function setAccountType(?string $accountType): self;

    /**
     * @return string|null
     */
    public function getSecCode(): ?string;

    /**
     * @param string|null $secCode
     * @return self
     */
    public function setSecCode(?string $secCode): self;

    /**
     * @return string|null
     */
    public function getSurcharge(): ?string;

    /**
     * @param string|null $surcharge
     * @return self
     */
    public function setSurcharge(?string $surcharge): self;

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;

    /**
     * @param string|null $currency
     * @return self
     */
    public function setCurrency(?string $currency): self;

    /**
     * @return string|null
     */
    public function getPayment(): ?string;

    /**
     * @param string|null $paymentType
     * @return self
     */
    public function setPaymentType(?string $paymentType): self;

    /**
     * @return string|null
     */
    public function getProcessorId(): ?string;

    /**
     * @param string|null $processorId
     * @return self
     */
    public function setProcessorId(?string $processorId): self;

    /**
     * @return string|null
     */
    public function getAuthorizationCode(): ?string;

    /**
     * @param string|null $authorizationCode
     * @return self
     */
    public function setAuthorizationCode(?string $authorizationCode): self;


    /**
     * @return string|null
     */
    public function getPaymentToken(): ?string;

    /**
     * @param string|null $paymentToken
     * @return self
     */
    public function setPaymentToken(?string $paymentToken): self;

    /**
     * @return int|null
     */
    public function getCcnumber(): ?int;

    /**
     * @param int|null $ccNumber
     * @return self
     */
    public function setCcnumber(?int $ccNumber): self;

    /**
     * @return string|null
     */
    public function getCcExp(): ?string;

    /**
     * @param string|null $ccExp
     * @return self
     */
    public function setCcExp(?string $ccExp): self;

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string;

    /**
     * @param string|null $transactionId
     * @return self
     */
    public function setTransactionId(?string $transactionId): self;

    /**
     * @return string|null
     */
    public function getVoidReason(): ?string;

    /**
     * @param string|null $voidReason
     * @return self
     */
    public function setVoidReason(?string $voidReason): self;
}
