<?php


namespace App\Lib\Affiliates;

use App\Exceptions\ModuleHandlers\ModuleHandlerException;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Models\Affiliates\Affiliate;
use App\Models\Affiliates\AffiliatePermission;
use fileLogger AS Log;

/**
 * Handle deleting affiliates.
 * Class DestroyHandler
 * @package App\Lib\Affiliates
 */
class DestroyHandler extends ModuleHandler
{
    /**
     * Delete the affiliate
     * @throws ModuleHandlerException
     */
    public function performAction(): void
    {
        if ($this->resource = Affiliate::findOrFail($this->moduleRequest->get('id'))) {
            $this->resourceId = $this->resource->id;
            $this->destroyPermissions();
            $this->resource->delete();
        } else {
            throw new ModuleHandlerException(__METHOD__, 'affiliates.create-resource-failed');
        }
    }

    /**
     * Delete the associated permissions
     */
    protected function destroyPermissions(): void
    {
        $numDeleted = AffiliatePermission::where([
            ['affiliate_id', $this->resourceId]
        ])->delete();

        if ($numDeleted > 0) {
            Log::track(__METHOD__, "[{$numDeleted}] Affiliate permissions deleted for affiliate [{$this->resourceId}]", LOG_WARN);
        }
    }
}