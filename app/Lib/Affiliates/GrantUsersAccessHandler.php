<?php


namespace App\Lib\Affiliates;

use Illuminate\Support\Collection;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Affiliates\AffiliatePermission;

/**
 * Handle creating grant affiliate permissions for a user.
 * Class GrantUsersAccessHandler
 * @package App\Lib\Affiliates
 */
class GrantUsersAccessHandler extends ModuleHandler
{
    /**
     * @var array $userIds
     */
    protected array $userIds = [];

    /**
     * @var int $affiliateId
     */
    protected int $affiliateId = 0;

    /**
     * @var int $accessType
     */
    protected int $accessType = AffiliatePermission::ACCESS_TYPE_WHITELIST;

	/**
	 * GrantUsersAccessHandler constructor.
	 * @param ModuleRequest $moduleRequest
	 */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);
        $this->affiliateId = $this->moduleRequest->id;
        $this->userIds     = (new Collection($this->moduleRequest->users))
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
            'id'         => 'required|int|min:1|exists:mysql_slave.affiliates,id',
            'users'      => 'required|array',
            'users.*.id' => 'required|distinct|int|min:1|exists:mysql_slave.admin,admin_id,active,1,deleted,0'
        ];
        $this->friendlyAttributeNames = [
            'id'           => 'Affiliate ID',
            'users'        => 'Users',
            'users.*.id'   => 'User ID'
        ];
    }

    /**
     * Create the affiliate permissions of the proper access type.
     */
    protected function createPermissions()
    {
        // Wipe existing affiliate permissions related to target users
        //
        AffiliatePermission::where([
            ['affiliate_id', $this->affiliateId]
        ])
            ->whereIn('user_id', $this->userIds)
            ->delete();

        foreach ($this->userIds as $userId) {
            AffiliatePermission::create([
                'affiliate_id'   => $this->affiliateId,
                'user_id'        => $userId,
                'access_type_id' => $this->accessType,
            ]);
        }
    }
}
