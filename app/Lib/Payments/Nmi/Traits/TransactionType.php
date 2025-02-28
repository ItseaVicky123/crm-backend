<?php
/**
 * Provides functionality for storing and interrogating
 * the type of transaction we're working with
 */

namespace App\Lib\Payments\Nmi\Traits;

use App\Lib\Payments\Nmi\Classes\NmiRequest;

trait TransactionType
{
    /**
     * The type of transaction that was processed.
     * Values: One of the NmiRequest::X_TRANSACTION constants
     * @var string|null
     */
    protected ?string $transactionType = null;

    /**
     * @return bool
     */
    public function isAuth(): bool
    {
        return $this->transactionType === NmiRequest::AUTH_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isSale(): bool
    {
        return $this->transactionType === NmiRequest::SALE_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isCredit(): bool
    {
        return $this->transactionType === NmiRequest::CREDIT_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isValidate(): bool
    {
        return $this->transactionType === NmiRequest::VALIDATE_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isOffline(): bool
    {
        return $this->transactionType === NmiRequest::OFFLINE_SALE_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isCapture(): bool
    {
        return $this->transactionType === NmiRequest::CAPTURE_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isRefund(): bool
    {
        return $this->transactionType === NmiRequest::REFUND_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isVoid(): bool
    {
        return $this->transactionType === NmiRequest::VOID_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->transactionType === NmiRequest::UPDATE_TRANSACTION;
    }

    /**
     * @return bool
     */
    public function needsFullInfo(): bool
    {
        return $this->isAuth() || $this->isSale() || $this->isUpdate() || $this->isOffline();
    }
}