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
 * Class ApiUserPermission
 * @package App\Models
 */
class ApiUserPermission extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'api_user_method_jct';

    /**
     * @var array
     */
    protected $visible = [
        'api_user_id',
        'api_method_id',
        'method',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'method',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function api_user()
    {
        return $this->hasOne(ApiUser::class, 'membershipAPIUserId', 'api_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function api_user_methods()
    {
        return $this->hasManyThrough(
            ApiUser::class,
            ApiMethod::class,
            'membershipAPIUserId',
            'id',
            'api_user_id',
            'api_method_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function method()
    {
        return $this->hasOne(ApiMethod::class, 'id', 'api_method_id');
    }
}
