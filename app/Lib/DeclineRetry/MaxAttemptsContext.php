<?php

namespace App\Lib\DeclineRetry;

use App\Models\SubscriptionHoldType;

/**
 * Class MaxAttemptsContext
 * @package App\Lib\DeclineRetry
 */
class MaxAttemptsContext
{
    /**
     * @var bool $isDeclineModuleBlocked
     */
    protected bool $isDeclineModuleBlocked = false;

    /**
     * @var bool $isHardDecline
     */
    protected bool $isHardDecline = false;

    /**
     * @var bool $isInitialDunningMaxAttempts
     */
    protected bool $isInitialDunningMaxAttempts = false;

    /**
     * @param bool $isDeclineModuleBlocked
     * @return MaxAttemptsContext
     */
    public function setIsDeclineModuleBlocked(bool $isDeclineModuleBlocked): MaxAttemptsContext
    {
        $this->isDeclineModuleBlocked = $isDeclineModuleBlocked;
        return $this;
    }

    /**
     * @param bool $isHardDecline
     * @return MaxAttemptsContext
     */
    public function setIsHardDecline(bool $isHardDecline): MaxAttemptsContext
    {
        $this->isHardDecline = $isHardDecline;
        return $this;
    }

    /**
     * @param bool $isInitialDunningMaxAttempts
     * @return MaxAttemptsContext
     */
    public function setIsInitialDunningMaxAttempts(bool $isInitialDunningMaxAttempts): MaxAttemptsContext
    {
        $this->isInitialDunningMaxAttempts = $isInitialDunningMaxAttempts;
        return $this;
    }

    /**
     * Determine hold type in the max attempts decline retry
     * @return int
     */
    public function calculateHoldType(): int
    {
        $holdType = SubscriptionHoldType::DECLINE_SALVAGE;
        
        if ($this->isDeclineModuleBlocked || $this->isHardDecline) {
            $holdType = SubscriptionHoldType::HARD_DECLINE;
        } else if ($this->isInitialDunningMaxAttempts) {
            $holdType = SubscriptionHoldType::INITIAL_DUNNING;
        }
        
        return $holdType;
    }
}