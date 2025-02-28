<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 * @package App\Models
 */
class OrderNotificationHistory extends Model
{
    const CREATED_AT = 't_stamp';
    const UPDATED_AT = null;

    public $table = 'order_notification_history';

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];
}
