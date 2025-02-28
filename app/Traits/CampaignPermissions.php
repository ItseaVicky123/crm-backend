<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Builder;

/**
 * Trait CampaignPermissions
 * @package App\Traits
 */
trait CampaignPermissions
{
    /**
     * @var array
     */
    protected static $_campaignPermissions = [];

    /**
     * @param Request $request
     * @param Model   $model
     */
    protected function handleCampaignPermissions(Request $request, Model $model)
    {
        if (empty(self::$_campaignPermissions)) {
            self::$_campaignPermissions = $request->user()->campaign_permissions;
        }

        if (count(self::$_campaignPermissions)) {
            $model::addGlobalScope('campaign_permissions', function (Builder $builder) {
                $builder->whereIn('campaign_id', self::$_campaignPermissions);
            });
        }
    }

    /**
     * Boot method for models, apply global scope for existing user if applicable
     */
    protected static function campaignPermissionBoot() : void
    {
        if ($permissions = self::getUserCampaignPermissions()) {
            static::addGlobalScope('campaign_permissions', function (Builder $builder) use ($permissions) {
                $builder->whereIn('campaign_id', $permissions);
            });
        }
    }

    /**
     * For non-campaign models, simply ensure the to-be-filtered campaign relationships exist
     */
    protected function applyCampaignPermissionsBoot(string $relationName = 'campaign') : void
    {
        if (self::getUserCampaignPermissions()) {
            static::addGlobalScope('campaign_permissions', function(Builder $builder) use ($relationName) {
                $builder->has($relationName);
            });
        }
    }

    /**
     * retrieving from cache for one minute because this file is loaded/unloaded multiple times and static storing only stays
     * during life of one script run cover all scenarios and reduces these 5 queries repeatedly hammering the system
     * for data that doesnt change
     * @return array
     */
    protected static function getUserCampaignPermissions() : array
    {
        $userId    = $userId = \current_user();
        $cache_key = CRM_APP_KEY . "::user({$userId})::campaign_permissions";

        if (! apcu_exists($cache_key)) {
            if ($userId) {
                if ($user = User::find($userId)) {
                    if ($permissions = $user->campaign_permissions) {
                        self::$_campaignPermissions = $permissions;
                        apcu_store($cache_key, $permissions, 60);
                    }
                }
            }
        }
        else {
           self::$_campaignPermissions = apcu_fetch($cache_key);
        }

        return self::$_campaignPermissions;
    }
}
