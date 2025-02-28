<?php


namespace App\Lib\Affiliates;

use Illuminate\Support\Collection;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Affiliates\AffiliatePermission;

/**
 * Handle creating grant affiliate permissions for a user.
 * Class GrantAccessHandler
 * @package App\Lib\Affiliates
 */
class GrantAccessHandler extends ModuleHandler
{
    /**
     * @var array $affiliateIds
     */
    protected array $affiliateIds = [];

    /**
     * @var int $userId
     */
    protected int $userId = 0;

    /**
     * @var int $accessType
     */
    protected int $accessType = AffiliatePermission::ACCESS_TYPE_WHITELIST;

    /**
     * GrantAccessHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);
        $this->userId       = $this->moduleRequest->user_id;
        $this->affiliateIds = (new Collection($this->moduleRequest->affiliates))
            ->pluck('id')
            ->all();
    }

    /**
     * Grant affiliate access to user.
     */
    public function performAction(): void
    {
        $this->createPermissions();
    }

    /**
     * Define access validation rules
     */
    protected function beforeValidation(): void
    {
        $this->validationRules = [
            'user_id'         => 'required|int|min:1|exists:mysql_slave.admin,admin_id,active,1,deleted,0',
            'affiliates'      => 'required|array',
            'affiliates.*.id' => 'required|distinct|int|min:1|exists:mysql_slave.affiliates,id'
        ];
        $this->friendlyAttributeNames = [
            'user_id'         => 'User ID',
            'affiliates'      => 'Affiliates',
            'affiliates.*.id' => 'Affiliate ID'
        ];
    }

    /**
     * Create the affiliate permissions of the proper access type.
     */
    protected function createPermissions()
    {
        // Wipe existing affiliate permissions related to target affiliates
        //
        AffiliatePermission::where([
            ['user_id', $this->userId]
        ])
            ->whereIn('affiliate_id', $this->affiliateIds)
            ->delete();

        foreach ($this->affiliateIds as $affiliateId) {
            AffiliatePermission::create([
                'user_id'        => $this->userId,
                'affiliate_id'   => $affiliateId,
                'access_type_id' => $this->accessType,
            ]);
        }
    }
}
