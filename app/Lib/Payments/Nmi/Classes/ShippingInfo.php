<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\ShippingInfoProvider;

class ShippingInfo implements ShippingInfoProvider
{

    public const SHIPPING_CARRIER_USPS  = 'usps';
    public const SHIPPING_CARRIER_UPS   = 'ups';
    public const SHIPPING_CARRIER_FEDEX = 'fedex';
    public const SHIPPING_CARRIER_DHL   = 'dhl';

    /**
     * Freight or shipping amount included in the transaction amount
     * Default: '0.00'
     * Format: x.xx
     * @var string|null
     */
    protected ?string $shipping = null;

    /**
     * @var string|null
     */
    protected ?string $shippingFirstName = null;

    /**
     * @var string|null
     */
    protected ?string $shippingLastName = null;

    /**
     * @var string|null
     */
    protected ?string $shippingCompany = null;

    /**
     * @var string|null
     */
    protected ?string $shippingAddress1 = null;

    /**
     * @var string|null
     */
    protected ?string $shippingAddress2 = null;

    /**
     * @var string|null
     */
    protected ?string $shippingCity = null;

    /**
     * Format: CC
     * @var string|null
     */
    protected ?string $shippingState = null;

    /**
     * @var string|null
     */
    protected ?string $shippingZip = null;

    /**
     * Values: one of the SHIPPING_CARRIER_X constants
     * @var string|null
     */
    protected ?string $shippingCarrier = null;

    /**
     * Postal/ZIP code of the address where purchased goods will be delivered.
     * This field can be identical to the 'shipFromPostal' if the customer
     * is present and takes immediate possession of the goods.
     * @var string|null
     */
    protected ?string $shippingPostal = null;

    /**
     * Country codes are as shown in ISO 3166. Format: CC
     * @var string|null
     */
    protected ?string $shippingCountry = null;

    /**
     * @var string|null
     */
    protected ?string $shippingEmail = null;

    /**
     * Postal/ZIP code of the address from where purchased goods are being shipped,
     * defaults to merchant profile postal code
     * @var string|null
     */
    protected ?string $shipFromPostal = null;

    /**
     * 4 character international description code of the overall goods or services being supplied.
     * The acquirer or processor will provide a list of current codes
     * @var string|null
     */
    protected ?string $summaryCommodityCode = null;

    /**
     * Amount included in the transaction amount associated with the import of purchased goods
     * @var string|null
     */
    protected ?string $dutyAmount = null;

    /**
     * @return string|null
     */
    public function getCost(): ?string
    {
        return $this->shipping;
    }

    /**
     * @param string|null $shipping
     * @return ShippingInfoProvider
     */
    public function setCost(?string $shipping): ShippingInfoProvider
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostal(): ?string
    {
        return $this->shippingPostal;
    }

    /**
     * @param string|null $shippingPostal
     * @return ShippingInfoProvider
     */
    public function setPostal(?string $shippingPostal): ShippingInfoProvider
    {
        $this->shippingPostal = $shippingPostal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getShipFromPostal(): ?string
    {
        return $this->shipFromPostal;
    }

    /**
     * @param string|null $shipFromPostal
     * @return ShippingInfoProvider
     */
    public function setShipFromPostal(?string $shipFromPostal): ShippingInfoProvider
    {
        $this->shipFromPostal = $shipFromPostal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSummaryCommodityCode(): ?string
    {
        return $this->summaryCommodityCode;
    }

    /**
     * @param string|null $summaryCommodityCode
     * @return ShippingInfoProvider
     */
    public function setSummaryCommodityCode(?string $summaryCommodityCode): ShippingInfoProvider
    {
        $this->summaryCommodityCode = $summaryCommodityCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDutyAmount(): ?string
    {
        return $this->dutyAmount;
    }

    /**
     * @param string|null $dutyAmount
     * @return ShippingInfoProvider
     */
    public function setDutyAmount(?string $dutyAmount): ShippingInfoProvider
    {
        $this->dutyAmount = $dutyAmount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->shippingFirstName;
    }

    /**
     * @param string|null $shippingFirstName
     * @return ShippingInfoProvider
     */
    public function setFirstName(?string $shippingFirstName): ShippingInfoProvider
    {
        $this->shippingFirstName = $shippingFirstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->shippingLastName;
    }

    /**
     * @param string|null $shippingLastName
     * @return ShippingInfoProvider
     */
    public function setLastName(?string $shippingLastName): ShippingInfoProvider
    {
        $this->shippingLastName = $shippingLastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->shippingCompany;
    }

    /**
     * @param string|null $shippingCompany
     * @return ShippingInfoProvider
     */
    public function setCompany(?string $shippingCompany): ShippingInfoProvider
    {
        $this->shippingCompany = $shippingCompany;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress1(): ?string
    {
        return $this->shippingAddress1;
    }

    /**
     * @param string|null $shippingAddress1
     * @return ShippingInfoProvider
     */
    public function setAddress1(?string $shippingAddress1): ShippingInfoProvider
    {
        $this->shippingAddress1 = $shippingAddress1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->shippingAddress2;
    }

    /**
     * @param string|null $shippingAddress2
     * @return ShippingInfoProvider
     */
    public function setAddress2(?string $shippingAddress2): ShippingInfoProvider
    {
        $this->shippingAddress2 = $shippingAddress2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->shippingCity;
    }

    /**
     * @param string|null $shippingCity
     * @return ShippingInfoProvider
     */
    public function setCity(?string $shippingCity): ShippingInfoProvider
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->shippingState;
    }

    /**
     * @param string|null $shippingState
     * @return ShippingInfoProvider
     */
    public function setState(?string $shippingState): ShippingInfoProvider
    {
        $this->shippingState = $shippingState;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->shippingZip;
    }

    /**
     * @param string|null $shippingZip
     * @return ShippingInfoProvider
     */
    public function setZip(?string $shippingZip): ShippingInfoProvider
    {
        $this->shippingZip = $shippingZip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->shippingCountry;
    }

    /**
     * @param string|null $shippingCountry
     * @return ShippingInfoProvider
     */
    public function setCountry(?string $shippingCountry): ShippingInfoProvider
    {
        $this->shippingCountry = $shippingCountry;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->shippingEmail;
    }

    /**
     * @param string|null $shippingEmail
     * @return ShippingInfoProvider
     */
    public function setEmail(?string $shippingEmail): ShippingInfoProvider
    {
        $this->shippingEmail = $shippingEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCarrier(): ?string
    {
        return $this->shippingCarrier;
    }

    /**
     * @param string|null $shippingCarrier
     * @return ShippingInfo
     */
    public function setCarrier(?string $shippingCarrier): ShippingInfoProvider
    {
        $this->shippingCarrier = $shippingCarrier;

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
            'shipping'               => $this->shipping,
            'shipping_firstname'     => $this->shippingFirstName,
            'shipping_lastname'      => $this->shippingLastName,
            'shipping_company'       => $this->shippingCompany,
            'shipping_address1'      => $this->shippingAddress1,
            'shipping_address2'      => $this->shippingAddress2,
            'shipping_city'          => $this->shippingCity,
            'shipping_state'         => $this->shippingState,
            'shipping_zip'           => $this->shippingZip,
            'shipping_carrier'       => $this->shippingCarrier,
            'shipping_postal'        => $this->shippingPostal,
            'shipping_country'       => $this->shippingCountry,
            'shipping_email'         => $this->shippingEmail,
            'ship_from_postal'       => $this->shipFromPostal,
            'summary_commodity_code' => $this->summaryCommodityCode,
            'duty_amount'            => $this->dutyAmount,
        ];
    }
}


