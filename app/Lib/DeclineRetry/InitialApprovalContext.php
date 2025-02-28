<?php

namespace App\Lib\DeclineRetry;

use App\Models\ConfigSetting;
use App\Facades\SMC;

/**
 * Class InitialApprovalContext
 * @package App\Services\Dunning
 */
class InitialApprovalContext extends AbstractContext
{
    /**
     * This determines the range of seconds between duplicate declines and this approval
     * so that the system will not retry them if dunning was on
     */
    protected function loadDuplicateSecondsWindow(): void
    {
        if ($config = ConfigSetting::key('INITIAL_DUNNING_APPROVAL_DUPE_WINDOW')->first()) {
            $this->duplicateSecondsWindow = min((int) $config->value, self::DUPLICATE_MAX_SECONDS);
        }
    }

    /**
     * Process initial dunning entry point for approvals if feature enabled.
     * @param int $targetOrderId
     * @return InitialApprovalContext|null
     */
    public static function process(int $targetOrderId): ?InitialApprovalContext
    {
        if (SMC::check('INITIAL_DUNNING')) {
            return new self($targetOrderId);
        }

        return null;
    }
}
