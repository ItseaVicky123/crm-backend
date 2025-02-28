<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\TdsInfoProvider;

class TdsInfo implements TdsInfoProvider
{

    public const CARDHOLDER_AUTH_VERIFIED  = 'verified';
    public const CARDHOLDER_AUTH_ATTEMPTED = 'attempted';

    /**
     * 3DS authentication is either failed or could not be attempted;
     * possible reasons being both card and Issuing Bank are not secured by 3DS,
     * technical errors, or improper configuration.
     */
    public const ECI_FAILED_MC = 0;

    /**
     * MasterCard ECI indicating 3DS authentication was attempted but was not or could not be completed;
     * possible reasons being either the card or its Issuing Bank has yet to participate in 3DS,
     * or cardholder ran out of time to authorize.
     */
    public const ECI_NOT_COMPLETE_MC = 1;

    /**
     * MasterCard ECI indicating 3DS authentication is successful; both card and Issuing Bank are secured by 3DS.
     */
    public const ECI_SUCCESSFUL_MC = 2;


    /**
     * Visa ECI indicating 3DS authentication was successful; transactions are secured by 3DS.
     */
    public const ECI_SUCCESSFUL_VI = 5;

    /**
     * Visa ECI indicating authentication was attempted but was not or could not be completed;
     * possible reasons being either the card or its Issuing Bank has yet to participate in 3DS.
     */
    public const ECI_NOT_COMPLETE_VI = 6;

    /**
     * Visa 3DS authentication is either failed or could not be attempted; possible reasons being both
     * card and Issuing Bank are not secured by 3DS, technical errors, or improper configuration.
     */
    public const ECI_NOT_FAILED_VI = 7;

    /**
     * 3DS version 1 indicator
     */
    public const VERSION_102 = "1.0.2";

    /**
     * 3DS version 2 indicator
     */
    public const VERSION_2 = "2.0";

    /**
     * Set 3D Secure condition.
     * Values: one of the CARDHOLDER_AUTH_X constants
     * @var string|null
     */
    protected ?string $cardholderAuth = null;

    /**
     * E-commerce indicator, value returned by Directory Servers
     * (namely Visa, MasterCard, JCB, and American Express) indicating
     * the outcome of authentication attempted on transactions enforced by 3DS.
     * Values: one of the ECI_X constants
     * @var int|null
     */
    protected ?int $eci = null;

    /**
     * Cardholder authentication verification value.
     * Format: base64 encoded
     * @var string|null
     */
    protected ?string $cavv = null;

    /**
     * Cardholder authentication transaction id.
     * Format: base64 encoded
     * @var string|null
     */
    protected ?string $xid = null;

    /**
     * 3DSecure version.
     * values: one of the VERSION_X constants
     * @var string|null
     */
    protected ?string $threeDsVersion = null;

    /**
     * Directory Server Transaction ID. May be provided as part of 3DSecure 2.0 authentication.
     * Format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     * @var string|null
     */
    protected ?string $directoryServerId = null;

    /**
     * Specifies a payment gateway transaction id in order to associate payment information
     * with a Subscription or Customer Vault record.
     * Must be set with a 'recurring' or 'customer_vault' action.
     * @var string|null
     */
    protected ?string $sourceTransactionId = null;

    /**
     * @return string|null
     */
    public function getCardholderAuth(): ?string
    {
        return $this->cardholderAuth;
    }

    /**
     * @param string|null $cardholderAuth
     * @return TdsInfoProvider
     */
    public function setCardholderAuth(?string $cardholderAuth): TdsInfoProvider
    {
        $this->cardholderAuth = $cardholderAuth;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getEci(): ?int
    {
        return $this->eci;
    }

    /**
     * @param int|null $eci
     * @return TdsInfoProvider
     */
    public function setEci(?int $eci): TdsInfoProvider
    {
        $this->eci = $eci;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCavv(): ?string
    {
        return $this->cavv;
    }

    /**
     * @param string|null $cavv
     * @return TdsInfoProvider
     */
    public function setCavv(?string $cavv): TdsInfoProvider
    {
        $this->cavv = $cavv;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getXid(): ?string
    {
        return $this->xid;
    }

    /**
     * @param string|null $xid
     * @return TdsInfoProvider
     */
    public function setXid(?string $xid): TdsInfoProvider
    {
        $this->xid = $xid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getThreeDsVersion(): ?string
    {
        return $this->threeDsVersion;
    }

    /**
     * @param string|null $threeDsVersion
     * @return TdsInfoProvider
     */
    public function setThreeDsVersion(?string $threeDsVersion): TdsInfoProvider
    {
        $this->threeDsVersion = $threeDsVersion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDirectoryServerId(): ?string
    {
        return $this->directoryServerId;
    }

    /**
     * @param string|null $directoryServerId
     * @return TdsInfoProvider
     */
    public function setDirectoryServerId(?string $directoryServerId): TdsInfoProvider
    {
        $this->directoryServerId = $directoryServerId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceTransactionId(): ?string
    {
        return $this->sourceTransactionId;
    }

    /**
     * @param string|null $sourceTransactionId
     * @return TdsInfoProvider
     */
    public function setSourceTransactionId(?string $sourceTransactionId): TdsInfoProvider
    {
        $this->sourceTransactionId = $sourceTransactionId;

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
            'cardholder_auth'       => $this->cardholderAuth,
            'eci'                   => $this->eci,
            'cavv'                  => $this->cavv,
            'xid'                   => $this->xid,
            'three_ds_version'      => $this->threeDsVersion,
            'directory_server_id'   => $this->directoryServerId,
            'source_transaction_id' => $this->sourceTransactionId,
        ];
    }
}