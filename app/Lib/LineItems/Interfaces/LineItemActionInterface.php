<?php


namespace App\Lib\LineItems\Interfaces;

/**
 * Interface LineItemActionInterface
 * @package App\Lib\LineItems\Interfaces
 */
interface LineItemActionInterface
{
    /**
     * @return bool
     */
    public function resetRecurring(): bool;
}