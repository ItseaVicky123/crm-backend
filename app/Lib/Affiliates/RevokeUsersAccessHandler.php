<?php


namespace App\Lib\Affiliates;

use App\Models\Affiliates\AffiliatePermission;

/**
 * Handle creating revoke affiliate permissions for a user.
 * Class RevokeAccessHandler
 * @package App\Lib\Affiliates
 */
class RevokeUsersAccessHandler extends GrantUsersAccessHandler
{
	/**
	 * Grant affiliate access to user.
	 */
	public function performAction(): void
	{
		AffiliatePermission::where([
			['affiliate_id', $this->affiliateId]
		])
			->whereIn('user_id', $this->userIds)
			->delete();
	}
}
