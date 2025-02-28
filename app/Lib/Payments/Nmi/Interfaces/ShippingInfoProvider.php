<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface ShippingInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getCost(): ?string;

    /**
     * @param string|null $shipping
     * @return self
     */
    public  function setCost(?string $shipping): self;

    /**
     * @return string|null
     */
    public  function getPostal(): ?string;

    /**
     * @param string|null $shippingPostal
     * @return self
     */
    public  function setPostal(?string $shippingPostal): self;

    /**
     * @return string|null
     */
    public  function getShipFromPostal(): ?string;

    /**
     * @param string|null $shipFromPostal
     * @return self
     */
    public  function setShipFromPostal(?string $shipFromPostal): self;

    /**
     * @return string|null
     */
    public  function getSummaryCommodityCode(): ?string;

    /**
     * @param string|null $summaryCommodityCode
     * @return self
     */
    public  function setSummaryCommodityCode(?string $summaryCommodityCode): self;

    /**
     * @return string|null
     */
    public  function getDutyAmount(): ?string;

    /**
     * @param string|null $dutyAmount
     * @return self
     */
    public  function setDutyAmount(?string $dutyAmount): self;

    /**
     * @return string|null
     */
    public  function getFirstName(): ?string;

    /**
     * @param string|null $shippingFirstName
     * @return self
     */
    public  function setFirstName(?string $shippingFirstName): self;

    /**
     * @return string|null
     */
    public  function getLastName(): ?string;

    /**
     * @param string|null $shippingLastName
     * @return self
     */
    public  function setLastName(?string $shippingLastName): self;

    /**
     * @return string|null
     */
    public  function getCompany(): ?string;

    /**
     * @param string|null $shippingCompany
     * @return self
     */
    public  function setCompany(?string $shippingCompany): self;

    /**
     * @return string|null
     */
    public  function getAddress1(): ?string;

    /**
     * @param string|null $shippingAddress1
     * @return self
     */
    public  function setAddress1(?string $shippingAddress1): self;

    /**
     * @return string|null
     */
    public  function getAddress2(): ?string;

    /**
     * @param string|null $shippingAddress2
     * @return self
     */
    public  function setAddress2(?string $shippingAddress2): self;

    /**
     * @return string|null
     */
    public  function getCity(): ?string;

    /**
     * @param string|null $shippingCity
     * @return self
     */
    public  function setCity(?string $shippingCity): self;

    /**
     * @return string|null
     */
    public  function getState(): ?string;

    /**
     * @param string|null $shippingState
     * @return self
     */
    public  function setState(?string $shippingState): self;

    /**
     * @return string|null
     */
    public  function getZip(): ?string;

    /**
     * @param string|null $shippingZip
     * @return self
     */
    public  function setZip(?string $shippingZip): self;

    /**
     * @return string|null
     */
    public  function getCountry(): ?string;

    /**
     * @param string|null $shippingCountry
     * @return self
     */
    public  function setCountry(?string $shippingCountry): self;

    /**
     * @return string|null
     */
    public  function getEmail(): ?string;

    /**
     * @param string|null $shippingEmail
     * @return self
     */
    public  function setEmail(?string $shippingEmail): self;

    /**
     * @return string|null
     */
    public function getCarrier(): ?string;

    /**
     * @param string|null $shippingCarrier
     * @return self
     */
    public function setCarrier(?string $shippingCarrier): self;
}
