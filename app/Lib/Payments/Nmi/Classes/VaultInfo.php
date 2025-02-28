<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\VaultInfoProvider;

class VaultInfo implements VaultInfoProvider
{

    public const VAULT_ACTION_ADD_CUSTOMER    = 'add_customer';
    public const VAULT_ACTION_UPDATE_CUSTOMER = 'update_customer';

    public const INITIATED_BY_CUSTOMER = 'customer';
    public const INITIATED_BY_MERCHANT = 'merchant';

    /**
     * Used when processing the initial transaction in which you are storing
     * a customer's payment details (customer credentials) in the Customer
     * Vault or other third-party payment storage system.
     */
    public const STORED_CREDENTIAL_TYPE_STORED = 'stored';

    /**
     * Used when processing a subsequent or follow-up transaction using the
     * customer payment details (customer credentials) you have already stored
     * to the Customer Vault or third-party payment storage method.
     */
    public const STORED_CREDENTIAL_TYPE_USED = 'used';

    /**
     * Associate payment information with a Customer Vault record if the transaction is successful.
     * Values: one of the VAULT_ACTION_X constants
     * @var string|null
     */
    protected ?string $customerVault = null;

    /**
     * Specifies a customer vault id.
     * If not set, the payment gateway will randomly generate a customer vault id.
     * @var string|null
     */
    protected ?string $customerVaultId = null;

    /**
     * Who initiated the transaction
     * Values: one of the INITIATED_BY_X constants
     * @var string|null
     */
    protected ?string $initiatedBy = null;

    /**
     * Original payment gateway transaction id.
     * @var string|null
     */
    protected ?string $initialTransactionId = null;

    /**
     * The indicator of the stored credential.
     * Values: one of the STORED_CREDENTIAL_TYPE_X constants
     * @var string|null
     */
    protected ?string $storedCredentialIndicator = null;

    /**
     * @return string|null
     */
    public function getCustomerVault(): ?string
    {
        return $this->customerVault;
    }

    /**
     * @param string|null $customerVault
     * @return VaultInfoProvider
     */
    public function setCustomerVault(?string $customerVault): VaultInfoProvider
    {
        $this->customerVault = $customerVault;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerVaultId(): ?string
    {
        return $this->customerVaultId;
    }

    /**
     * @param string|null $customerVaultId
     * @return VaultInfoProvider
     */
    public function setCustomerVaultId(?string $customerVaultId): VaultInfoProvider
    {
        $this->customerVaultId = $customerVaultId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInitiatedBy(): ?string
    {
        return $this->initiatedBy;
    }

    /**
     * @param string|null $initiatedBy
     * @return VaultInfoProvider
     */
    public function setInitiatedBy(?string $initiatedBy): VaultInfoProvider
    {
        $this->initiatedBy = $initiatedBy;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInitialTransactionId(): ?string
    {
        return $this->initialTransactionId;
    }

    /**
     * @param string|null $initialTransactionId
     * @return VaultInfoProvider
     */
    public function setInitialTransactionId(?string $initialTransactionId): VaultInfoProvider
    {
        $this->initialTransactionId = $initialTransactionId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStoredCredentialIndicator(): ?string
    {
        return $this->storedCredentialIndicator;
    }

    /**
     * @param string|null $storedCredentialIndicator
     * @return VaultInfoProvider
     */
    public function setStoredCredentialIndicator(?string $storedCredentialIndicator): VaultInfoProvider
    {
        $this->storedCredentialIndicator = $storedCredentialIndicator;

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
            'customer_vault'              => $this->customerVault,
            'customer_vault_id'           => $this->customerVaultId,
            'initiated_by'                => $this->initiatedBy,
            'initial_transaction_id'      => $this->initialTransactionId,
            'stored_credential_indicator' => $this->storedCredentialIndicator,
        ];
    }
}