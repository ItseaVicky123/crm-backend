<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface DescriptorInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public function getDescriptor(): ?string;

    /**
     * @param string|null $descriptor
     * @return self
     */
    public function setDescriptor(?string $descriptor): self;

    /**
     * @return string|null
     */
    public function getDescriptorPhone(): ?string;

    /**
     * @param string|null $descriptorPhone
     * @return self
     */
    public function setDescriptorPhone(?string $descriptorPhone): self;

    /**
     * @return string|null
     */
    public function getDescriptorAddress(): ?string;

    /**
     * @param string|null $descriptorAddress
     * @return self
     */
    public function setDescriptorAddress(?string $descriptorAddress): self;

    /**
     * @return string|null
     */
    public function getDescriptorCity(): ?string;

    /**
     * @param string|null $descriptorCity
     * @return self
     */
    public function setDescriptorCity(?string $descriptorCity): self;

    /**
     * @return string|null
     */
    public function getDescriptorState(): ?string;

    /**
     * @param string|null $descriptorState
     * @return self
     */
    public function setDescriptorState(?string $descriptorState): self;

    /**
     * @return string|null
     */
    public function getDescriptorPostal(): ?string;

    /**
     * @param string|null $descriptorPostal
     * @return self
     */
    public function setDescriptorPostal(?string $descriptorPostal): self;

    /**
     * @return string|null
     */
    public function getDescriptorCountry(): ?string;

    /**
     * @param string|null $descriptorCountry
     * @return self
     */
    public function setDescriptorCountry(?string $descriptorCountry): self;

    /**
     * @return string|null
     */
    public function getDescriptorMcc(): ?string;

    /**
     * @param string|null $descriptorMcc
     * @return self
     */
    public function setDescriptorMcc(?string $descriptorMcc): self;

    /**
     * @return string|null
     */
    public function getDescriptorMerchantId(): ?string;

    /**
     * @param string|null $descriptorMerchantId
     * @return self
     */
    public function setDescriptorMerchantId(?string $descriptorMerchantId): self;

    /**
     * @return string|null
     */
    public function getDescriptorUrl(): ?string;

    /**
     * @param string|null $descriptorUrl
     * @return self
     */
    public function setDescriptorUrl(?string $descriptorUrl): self;
}
