<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

class PlatformKey extends Model
{
    use Eloquence;

    protected $table      = 'v_api_provider_authorization';
    protected $primaryKey = 'api_user_id';

    protected $visible = [
        'api_user_id',
        'platform_key',
        'ip_address',
    ];

    public function api_user()
    {
        return $this->belongsTo(ApiUser::class, 'api_user_id', 'membershipAPIUserId');
    }
}
