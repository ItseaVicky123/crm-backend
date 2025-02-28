<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class EmailLog
 * @package App\Models
 */
class EmailLog extends Model
{

    /**
     * @var string
     */
    protected $table = 'notification_log';

    /**
     * @param Builder $query
     * @param int $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

   /**
    * @param Builder $query
    * @param int $typeId
    * @return mixed
    */
    public function scopeOfType(Builder $query, int $typeId)
    {
        return $query->where('event_type_id', $typeId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(NotificationEvent::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event_type()
    {
        return $this->belongsTo(NotificationEventType::class, 'event_type_id');
    }
}
