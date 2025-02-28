<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class ApiUser
 * @package App\Models
 */
class ApiUser extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, Eloquence, Mappable;

    const UPDATED_AT        = 'updatedOn';
    const CREATED_AT        = 'createdOn';
    const INTERNAL          = 1;
    const NON_INTERNAL      = 0;
    const INTERNAL_USERNAME = 'Sticky Internal User';

    /**
     * @var string
     */
    protected $table = 'membership_api_users';

    /**
     * @var string
     */
    protected $primaryKey = 'membershipAPIUserId';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'username',
        'methods',
        'password',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'        => 'membershipAPIUserId',
        'is_active' => 'active',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'platform_key',
        'user_id',
        'methods',
    ];

    protected $fromPlatformKey;

    /**
     * @var null|User
     */
    protected static $admin = null;

    /**
     * @var null|int
     */
    protected static $adminId = User::API;

    /**
     * @var array
     */
    protected $valid_permissions = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function platform_key()
    {
        return $this->hasOne(PlatformKey::class, 'api_user_id', 'membershipAPIUserId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'api_user_id', 'membershipAPIUserId');
    }

    /**
     * @return mixed
     */
    public function getUserIdAttribute()
    {
        if (! isset(self::$admin)) {
            self::$admin   = $this->user()->first() ?? User::find(User::API);
            self::$adminId = (int) self::$admin ? self::$admin->id : User::API;
        }

        return self::$adminId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(ApiUserPermission::class, 'api_user_id', 'membershipAPIUserId');
    }

    /**
     * @param string $method
     * @return bool
     */
    public function hasPermission($method = '')
    {
        if ($this->is_internal || ($this->fromPlatformKey && (int) $this->fromPlatformKey->is_super_user)) {
            return true;
        }

        if (!count($this->valid_permissions)) {
            $permissions = $this->permissions()
                ->with(['method'])
                ->get();

            foreach ($permissions as $permission) {
                $this->valid_permissions[$permission->method->id] = $permission->method->name;
            }
        }

        // If numeric, check keys
        $valid = in_array($method,
            is_numeric($method)
                ? array_keys($this->valid_permissions)
                : $this->valid_permissions
        );

        if (! $valid) {
            return false;
        }

        // Now we know they have access to the method, lets make sure
        // it is within the API Limits
        //
        $api_enforcer = new \api_enforcement([
            'username'  => $this->getAttribute('username'),
            'client_db' => (defined('DB_DATABASE') ? DB_DATABASE : ''),
            'method'    => (is_numeric($method) ? $this->valid_permissions[$method] : $method),
        ]);

        if (! $api_enforcer->enforce_api_limit()->is_valid_session) {
            return false;
        }

        if (! $api_enforcer->safe_ip_address()) {
            return false;
        }

        return true;
    }

    /**
     * @param $campaignId
     * @return bool
     */
    public function validateCampaignPermission($campaignId = null): bool
    {
        if (! empty($this->campaign_permissions) && $campaignId) {

            return in_array($campaignId, $this->campaign_permissions);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isSuperPlatform()
    {
       return $this->fromPlatformKey && $this->fromPlatformKey->is_super_user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaign_permissions()
    {
        return $this->hasMany(ApiUserCampaignPermission::class, 'user_id', 'membershipAPIUserId');
    }

    /**
     * @return array
     */
    public function getCampaignPermissionsAttribute()
    {
        $campaigns = [];

        foreach ($this->campaign_permissions()->get() as $campaign) {
            $campaigns[] = $campaign->campaign_id;
        }

        return $campaigns;
    }

    /**
     * @param PlatformKey $pk
     */
    public function isFromPlatformKey(PlatformKey $pk)
    {
        $this->fromPlatformKey = $pk;
    }
}
