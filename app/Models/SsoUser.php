<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SsoUser extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;

    protected $table = 'sso_user_jct';
    protected $maps = [
        'is_active' => 'active'
    ];
    protected $appends = [
       'provider',
    ];

    public function call_center_provider()
    {
       return $this->hasOne(CallCenterProvider::class, 'account_id', 'provider_account_id');
    }

    public function getProviderAttribute()
    {
       return $this->call_center_provider()->first();
    }
}
