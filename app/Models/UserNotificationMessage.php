<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class UserNotificationMessage
 * @package App\Models
 */
class UserNotificationMessage extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';

    const LIMIT = 100;

    /**
     * @var int
     */
    public $perPage = self::LIMIT;

    /**
     * @var string
     */
    public $table = 'notification_message_user_jct';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'is_read',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'message',
        'title',
        'message_id',
        'created_at',
        'is_read',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'message',
        'title',
        'created_at',
        'is_read',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at' => 'date_in',
        'is_read'    => 'read_flag',
    ];

    /**
     * @var null|NotificationMessage
     */
    protected $message;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'admin_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function message()
    {
        return $this->hasOne(NotificationMessage::class, 'id', 'message_id');
    }

    /**
     * @return mixed
     */
    protected function getMessageAttribute()
    {
        return $this->getMessage()->message;
    }

    /**
     * @return mixed
     */
    protected function getTitleAttribute()
    {
        return $this->getMessage()->title;
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getMessage()
    {
        if (! isset($this->message)) {
            $this->message = $this->message()->first();
        }

        return $this->message;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeRead($query)
    {
        return $query->where('read_flag', 1);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeUnread($query)
    {
        return $query->where('read_flag', 0);
    }
}
