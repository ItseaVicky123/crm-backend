<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface VaultInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getCustomerVault(): ?string;

    /**
     * @param string|null $customerVault
     * @return self
     */
    public  function setCustomerVault(?string $customerVault): self;

    /**
     * @return string|null
     */
    public  function getCustomerVaultId(): ?string;

    /**
     * @param string|null $customerVaultId
     * @return self
     */
    public  function setCustomerVaultId(?string $customerVaultId): self;

    /**
     * @return string|null
     */
    public  function getInitiatedBy(): ?string;

    /**
     * @param string|null $initiatedBy
     * @return self
     */
    public  function setInitiatedBy(?string $initiatedBy): self;

    /**
     * @return string|null
     */
    public  function getInitialTransactionId(): ?string;

    /**
     * @param string|null $initialTransactionId
     * @return self
     */
    public  function setInitialTransactionId(?string $initialTransactionId): self;

    /**
     * @return string|null
     */
    public  function getStoredCredentialIndicator(): ?string;

    /**
     * @param string|null $storedCredentialIndicator
     * @return self
     */
    public  function setStoredCredentialIndicator(?string $storedCredentialIndicator): self;

}
