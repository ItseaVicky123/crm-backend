<?php
/**
 * Provides descriptor (ie what appears on a customer's billing statement) for supported NMI processors
 */

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\DescriptorInfoProvider;

class DescriptorInfo implements DescriptorInfoProvider
{

    /**
     * @var null|string
     */
    protected ?string $descriptor = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorPhone = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorAddress = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorCity = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorState = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorPostal = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorCountry = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorMcc = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorMerchantId = null;

    /**
     * @var null|string
     */
    protected ?string $descriptorUrl = null;

    /**
     * @return string|null
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param string|null $descriptor
     * @return DescriptorInfoProvider
     */
    public function setDescriptor(?string $descriptor): DescriptorInfoProvider
    {
        $this->descriptor = $descriptor;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorPhone(): ?string
    {
        return $this->descriptorPhone;
    }

    /**
     * @param string|null $descriptorPhone
     * @return DescriptorInfoProvider
     */
    public function setDescriptorPhone(?string $descriptorPhone): DescriptorInfoProvider
    {
        $this->descriptorPhone = $descriptorPhone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorAddress(): ?string
    {
        return $this->descriptorAddress;
    }

    /**
     * @param string|null $descriptorAddress
     * @return DescriptorInfoProvider
     */
    public function setDescriptorAddress(?string $descriptorAddress): DescriptorInfoProvider
    {
        $this->descriptorAddress = $descriptorAddress;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorCity(): ?string
    {
        return $this->descriptorCity;
    }

    /**
     * @param string|null $descriptorCity
     * @return DescriptorInfoProvider
     */
    public function setDescriptorCity(?string $descriptorCity): DescriptorInfoProvider
    {
        $this->descriptorCity = $descriptorCity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorState(): ?string
    {
        return $this->descriptorState;
    }

    /**
     * @param string|null $descriptorState
     * @return DescriptorInfoProvider
     */
    public function setDescriptorState(?string $descriptorState): DescriptorInfoProvider
    {
        $this->descriptorState = $descriptorState;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorPostal(): ?string
    {
        return $this->descriptorPostal;
    }

    /**
     * @param string|null $descriptorPostal
     * @return DescriptorInfoProvider
     */
    public function setDescriptorPostal(?string $descriptorPostal): DescriptorInfoProvider
    {
        $this->descriptorPostal = $descriptorPostal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorCountry(): ?string
    {
        return $this->descriptorCountry;
    }

    /**
     * @param string|null $descriptorCountry
     * @return DescriptorInfoProvider
     */
    public function setDescriptorCountry(?string $descriptorCountry): DescriptorInfoProvider
    {
        $this->descriptorCountry = $descriptorCountry;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorMcc(): ?string
    {
        return $this->descriptorMcc;
    }

    /**
     * @param string|null $descriptorMcc
     * @return DescriptorInfoProvider
     */
    public function setDescriptorMcc(?string $descriptorMcc): DescriptorInfoProvider
    {
        $this->descriptorMcc = $descriptorMcc;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorMerchantId(): ?string
    {
        return $this->descriptorMerchantId;
    }

    /**
     * @param string|null $descriptorMerchantId
     * @return DescriptorInfoProvider
     */
    public function setDescriptorMerchantId(?string $descriptorMerchantId): DescriptorInfoProvider
    {
        $this->descriptorMerchantId = $descriptorMerchantId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptorUrl(): ?string
    {
        return $this->descriptorUrl;
    }

    /**
     * @param string|null $descriptorUrl
     * @return DescriptorInfoProvider
     */
    public function setDescriptorUrl(?string $descriptorUrl): DescriptorInfoProvider
    {
        $this->descriptorUrl = $descriptorUrl;

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
            'descriptor'             => $this->descriptor,
            'descriptor_phone'       => $this->descriptorPhone,
            'descriptor_address'     => $this->descriptorAddress,
            'descriptor_city'        => $this->descriptorCity,
            'descriptor_state'       => $this->descriptorState,
            'descriptor_postal'      => $this->descriptorPostal,
            'descriptor_country'     => $this->descriptorCountry,
            'descriptor_mcc'         => $this->descriptorMcc,
            'descriptor_merchant_id' => $this->descriptorMerchantId,
            'descriptor_url'         => $this->descriptorUrl,
        ];
    }
}