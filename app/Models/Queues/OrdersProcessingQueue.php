<?php

namespace App\Models\Queues;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrdersProcessingQueue
 * @package App\Models\Queues
 *
 * @property int $id
 * @property int $orders_id
 * @property int $admin_id
 * @property string $queue_type
 * @property DateTime $queue_date
 * @property DateTime $update_in
 * @property bool $in_process
 * @property string $session_id
 * @property int $delay_count
 * @property int $not_before_hour
 * @property DateTime|null $retry_at
 *
 * @method static create(array $attributes = [])
 * @method static Builder|OrdersProcessingQueue where($column, $operator = null, $value = null, $boolean = 'and')
 */
class OrdersProcessingQueue extends Model
{
    protected $table      = 'orders_processing_queue';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'orders_id',
        'admin_id',
        'queue_type',
        'queue_date',
        'update_in',
        'in_process',
        'session_id',
        'delay_count',
        'not_before_hour',
        'retry_at'
    ];

    protected $casts = [
        'queue_date'      => 'datetime',
        'update_in'       => 'datetime',
        'in_process'      => 'boolean',
        'delay_count'     => 'integer',
        'not_before_hour' => 'integer',
        'retry_at'        => 'datetime'
    ];
}