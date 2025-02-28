<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface TdsInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getCardholderAuth(): ?string;

    /**
     * @param string|null $cardholderAuth
     * @return self
     */
    public  function setCardholderAuth(?string $cardholderAuth): self;

    /**
     * @return int|null
     */
    public  function getEci(): ?int;

    /**
     * @param int|null $eci
     * @return self
     */
    public  function setEci(?int $eci): self;

    /**
     * @return string|null
     */
    public  function getCavv(): ?string;

    /**
     * @param string|null $cavv
     * @return self
     */
    public  function setCavv(?string $cavv): self;

    /**
     * @return string|null
     */
    public  function getXid(): ?string;

    /**
     * @param string|null $xid
     * @return self
     */
    public  function setXid(?string $xid): self;

    /**
     * @return string|null
     */
    public  function getThreeDsVersion(): ?string;

    /**
     * @param string|null $threeDsVersion
     * @return self
     */
    public  function setThreeDsVersion(?string $threeDsVersion): self;

    /**
     * @return string|null
     */
    public  function getDirectoryServerId(): ?string;

    /**
     * @param string|null $directoryServerId
     * @return self
     */
    public  function setDirectoryServerId(?string $directoryServerId): self;

    /**
     * @return string|null
     */
    public  function getSourceTransactionId(): ?string;

    /**
     * @param string|null $sourceTransactionId
     * @return self
     */
    public  function setSourceTransactionId(?string $sourceTransactionId): self;

}
