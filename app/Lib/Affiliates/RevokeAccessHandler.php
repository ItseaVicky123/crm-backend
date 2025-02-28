<?php


namespace App\Lib\Affiliates;

use App\Models\Affiliates\AffiliatePermission;

/**
 * Handle creating revoke affiliate permissions for a user.
 * Class RevokeAccessHandler
 * @package App\Lib\Affiliates
 */
class RevokeAccessHandler extends GrantAccessHandler
{
    /**
     * @var int $accessType
     */
    protected int $accessType = AffiliatePermission::ACCESS_TYPE_BLACKLIST;
}
