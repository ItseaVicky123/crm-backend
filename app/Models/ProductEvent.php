<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProductEvent
 * @package App\Models
 */
class ProductEvent extends Model
{

    use Eloquence;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'notification_product_event';

    /**
     * @var array
     */
    protected $fillable = [
        'event_id',
        'product_id',
    ];

    public function notificationEvents(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'event_id', 'id');
    }
}
