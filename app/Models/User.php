<?php

namespace App\Models;


use App\Exceptions\WeakPasswordException;
use App\Lib\Encryption\System;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\UserType as CampaignUserType;
use App\Models\Campaign\UserRole as CampaignUserRole;
use App\Models\User\Role;
use App\Models\Role as MenuRole;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Auth\Authorizable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class User
 * @package App\Models
 *
 * @property int $id
 * @property string $admin_fullname
 * @property string $sws_user_password
 * @property string $email
 *
 * @method static User find(int $id)
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{

    use Authenticatable, Authorizable, Eloquence, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';
    const CREATED_BY = 'created_id';
    const UPDATED_BY = 'update_id';
    const SYSTEM     = 999999;
    const API        = 999998;
    const ADMIN      = 1;

    /**
     * @var string
     */
    protected $table = 'admin';

    /**
     * @var string
     */
    protected $primaryKey = 'admin_id';

    /**
     * @var bool|null
     */
    protected $isInternalRequest = null;

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'is_active',
        'username',
        'name',
        'email',
        'timezone',
        'department_id',
        'call_center_provider_id',
        'roles',
        'menu_items',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'id'                 => 'admin_id',
        'user_id'            => 'admin_id',
        'username'           => 'admin_name',
        'password'           => 'admin_password',
        'name'               => 'admin_fullname',
        'email'              => 'admin_email',
        'phone'              => 'phone_number',
        'allowed_ips'        => 'allowedIpAddress',
        'created_at'         => self::CREATED_AT,
        'updated_at'         => self::UPDATED_AT,
        'last_login_at'      => 'date_last_login',
        'disabled_at'        => 'date_login_disabled',
        'temp_password_at'   => 'temp_password_date_in',
        'password_reset_at'  => 'reset_password_date_in',
        'message_checked_at' => 'message_check_date',
        'created_by'         => self::CREATED_BY,
        'updated_by'         => self::UPDATED_BY,
        'is_active'          => 'active',
        'is_deleted'         => 'deleted',
        'is_portal_enabled'  => 'portal_access_flag',
        'is_sso'             => 'sso.is_active',
        'is_two_factor_auth' => 'two_factor_auth',
        'menu_csv'           => 'admin_menus',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'id',
        'is_active',
        'name',
        'username',
        'email',
        'is_two_factor_auth',
        'is_portal_enabled',
        'allowed_ips',
        'call_center_provider_id',
    ];

    /**
     * @var int[]
     */
    protected $scope_api_departments = [
        1,
        2,
        3,
        4,
        5,
        17,
        18,
        19,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'username' ,
        'password',
        'name',
        'email',
        'department_id',
        'timezone',
        'phone',
        'is_two_factor_auth',
        'is_portal_enabled',
        'admin_menus',
    ];

    /**
     * @var int[]
     */
    protected $limelight_admins = [
        self::API,
        self::SYSTEM,
    ];

    /**
     * @var int[]
     */
    protected $attributes = [
        'active'  => 1,
        'deleted' => 0,
    ];

    /**
     * @var int
     */
    public $perPage = 100;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->created_by = get_current_user_id();
        });

        static::updating(function ($user) {
            $user->updated_by = get_current_user_id();
        });

        static::deleting(function ($user) {
            $user->updated_by = get_current_user_id();
        });
    }

    /**
     * @param $value
     * @return $this
     */
    protected function setAdminPasswordAttribute($value)
    {
        $this->attributes['admin_password'] = System::encrypt($value);

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    protected function setAllowedIpAddressAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['allowedIpAddress'] = implode(',', $value);
        } elseif (is_string($value)) {
            $this->attributes['allowedIpAddress'] = $value;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getAllowedIpAddressAttribute()
    {
        $ips = $this->attributes['allowedIpAddress'] ?? '';

        return strlen($ips)
            ? explode(',', $ips)
            : [];
    }

    /**
     * @return bool
     */
    public function getIsSuperAttribute()
    {
        return $this->admin_id == 1;
    }

    /**
     * @return mixed
     */
    public function getPasswordRawAttribute()
    {
        return System::decrypt($this->password);
    }

    /**
     * @param      $new_password
     * @param bool $temp
     *
     * @return bool
     * @throws WeakPasswordException
     */
    public function updatePassword($new_password, $temp = false)
    {
        if ($regex = config('auth.passwords.regex')) {
            if (!Validator::make(['password' => $new_password,], ['password' => "regex:{$regex}"])->passes()) {
                throw new WeakPasswordException();
            }
        }

        $this->password             = System::encrypt($new_password);
        $this->reset_password_force = (int) $temp;
        $this->temp_password        = null;
        $this->temp_password_at     = null;
        $this->password_reset_at    = Carbon::now();

        return $this->save();
    }

    /**
     * @param $menu_id
     * @return bool
     */
    public function hasMenuRole($menu_id)
    {
        return $this->is_super || check_menu_permissions($menu_id);
    }

    /**
     * @param $role_id
     * @return bool
     */
    public function hasRole($role_id)
    {
        return $this->is_super || CheckRole($role_id);
    }

    /**
     * @param $role_ids
     * @return bool
     */
    public function hasAnyRole($role_ids)
    {
        return $this->is_super || CheckRole($role_ids, true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function roles()
    {
        return $this->hasManyThrough(
            MenuRole::class,
            Role::class,
            'user_id',
            'id',
            'admin_id',
            'role_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_roles()
    {
        return $this->hasMany(Role::class, 'user_id', 'admin_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function menu_items()
    {
        return $this->hasManyThrough(
            MenuItem::class,
            Role::class,
            'user_id',
            'id',
            'admin_id',
            'mid'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    protected function getMenuItemsAttribute()
    {
        return $this->menu_items()
            ->get()
            ->sortBy('id')
            ->sortBy('order')
            ->unique('id')
            ->values();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sso()
    {
        return $this->hasOne(SsoUser::class, 'admin_id', 'admin_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function api_user()
    {
        return $this->belongsTo(ApiUser::class, 'api_user_id', 'membershipAPIUserId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaign_permissions()
    {
        return $this->hasMany(UserCampaignPermission::class, 'user_id', 'admin_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function campaign_permission_type()
    {
        return $this->hasOne(CampaignUserType::class, 'user_id', 'admin_id');
    }

    /**
     * @return array
     */
    public function getCampaignPermissionsAttribute()
    {
        $permissions      = [];
        $campaignsBuilder = UserCampaignPermission::where('user_id', $this->getAttribute('id'))
            ->where('active', 1);
        $count = $campaignsBuilder->count();

        if ($count) {
            $campaigns         = $campaignsBuilder->get();
            $allCampaignsCount = Campaign::withoutGlobalScope('campaign_permissions')->count();

            if ($campaigns->first()->type_id == CampaignUserType::TYPE_ALLOW) {
                if ($count != $allCampaignsCount) {
                    $permissions = $campaigns
                        ->pluck('campaign_id')
                        ->toArray();
                }
            } else {
                $allCampaigns = Campaign::all()
                    ->pluck('id')
                    ->toArray();
                $disallowed   = $campaigns
                    ->pluck('campaign_id')
                    ->toArray();
                $permissions  = array_diff($allCampaigns, $disallowed);
            }
        }

        return $permissions;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaign_roles()
    {
        return $this->hasMany(CampaignUserRole::class, 'admin_id', 'admin_id');
    }

    /**
     * @param Request $request
     * Determine if this is an internal API request
     * User will be internal and ApiUser is external
     * @return $this
     */
    public function isInternalRequest(Request $request)
    {
        $this->isInternalRequest = ($request->user() instanceof User);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (!isset($this->isInternalRequest)) {
            $this->isInternalRequest(request());
        }

        if (!$this->isInternalRequest) {
            $this->makeHidden(['username']);
        }

        return parent::toArray();
    }

    /**
     * @return mixed
     */
    public function scopeForApi()
    {
        return $this->whereIn('department_id', $this->scope_api_departments)
            ->orWhereIn('admin_id', $this->limelight_admins);
    }

    /**
     * @return int
     */
    public function getActiveAttribute()
    {
        return (in_array($this->admin_id, $this->limelight_admins) ? 1 : $this->attributes['active']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(UserDepartment::class, 'department_id', 'id');
    }

    /**
     * @return int
     */
    public function getCallCenterProviderIdAttribute()
    {
        $sso = $this->sso;

        return ($sso ? $sso->provider_account_id : 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notification_messages()
    {
        return $this->hasMany(UserNotificationMessage::class, 'user_id', 'admin_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotificationMessagesAttribute()
    {
        return $this->notification_messages()
            ->whereHas('message')
            ->orderBy('is_read', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->limit(UserNotificationMessage::LIMIT)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userSetupGuideOptOut()
    {
        return $this->hasOne(UserOptOut::class, 'user_id', 'admin_id')
            ->where('feature_id', UserSetupGuide::FEATURE_ID);
    }

    /**
     * @return int
     */
    public function getSetupGuideOptOutAttribute()
    {
        return (int) $this->userSetupGuideOptOut()->exists();
    }

    /**
     * @return array
     */
    public function getPlaceOrderCampaignsAttribute()
    {
        return get_user_campaign_permission_by_role(CampaignUserRole::PLACE_ORDER_ROLE_ID);
    }
}
