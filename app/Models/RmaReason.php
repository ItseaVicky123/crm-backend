<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

class RmaReason extends Model
{
    use LimeSoftDeletes, Eloquence, Mappable;

    const CREATED_AT = 'createdOn';

    protected $primaryKey = 'RMAReasonCodesId';
    protected $table      = 'RMA_reason_codes';
    protected $visible    = [
        'id',
        'name',
        'description',
        'created_at',
        'action_id',
        'expired_action_id',
        'is_core_option',
    ];
    protected $appends = [
        'id',
        'created_at',
        'is_core_option',
    ];
    protected $maps = [
        'id'             => 'RMAReasonCodesId',
        'created_at'     => 'createdOn',
        'is_core_option' => 'core_option_flag',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'RMAReasonCodeId');
    }
}
