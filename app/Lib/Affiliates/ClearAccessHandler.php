<?php


namespace App\Lib\Affiliates;

use App\Lib\ModuleHandlers\ModuleHandler;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Affiliates\AffiliatePermission;
use fileLogger AS Log;

/**
 * Handle removing affiliate permissions from a user.
 * Class ClearAccessHandler
 * @package App\Lib\Affiliates
 */
class ClearAccessHandler extends ModuleHandler
{
    /**
     * @var int $userId
     */
    protected int $userId = 0;

    /**
     * ClearAccessHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);
        $this->userId = $this->moduleRequest->user_id;
    }

    /**
     * Delete all affiliate permissions associated with user.
     */
    public function performAction(): void
    {
        $deleted = AffiliatePermission::where([
            ['user_id', $this->userId]
        ])->delete();

        if ($deleted > 0) {
            Log::track(__METHOD__, "[{$deleted}] Affiliate permissions deleted for user [{$this->userId}]", LOG_WARN);
        }
    }

    /**
     * Define access validation rules
     */
    protected function beforeValidation(): void
    {
        $this->validationRules = [
            'user_id'         => 'required|int|min:1|exists:mysql_slave.admin,admin_id',
        ];
        $this->friendlyAttributeNames = [
            'user_id'         => 'User ID',
        ];
    }
}
