<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class NotificationMessage
 * @package App\Models
 */
class NotificationMessage extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    const CREATED_AT = 'date_in';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'title',
        'message',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'  => 'active',
        'is_deleted' => 'deleted',
        'created_at' => 'date_in',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_notification_message()
    {
        return $this->belongsTo(UserNotificationMessage::class, 'message_id', 'id');
    }

}
