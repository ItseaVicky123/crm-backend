<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface NmiTransactionTypeProvider extends Arrayable
{
    /**
     * @return bool
     */
    public function isAuth(): bool;

    /**
     * @return bool
     */
    public function isSale(): bool;

    /**
     * @return bool
     */
    public function isCredit(): bool;

    /**
     * @return bool
     */
    public function isValidate(): bool;

    /**
     * @return bool
     */
    public function isOffline(): bool;

    /**
     * @return bool
     */
    public function isCapture(): bool;

    /**
     * @return bool
     */
    public function isRefund(): bool;

    /**
     * @return bool
     */
    public function isVoid(): bool;

    /**
     * @return bool
     */
    public function isUpdate(): bool;

    /**
     * @return bool
     */
    public function needsFullInfo(): bool;
}