<?php

namespace App\Models;

use App\Traits\ModelReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class HistoryNote
 * @package App\Models
 * @method static create(array $array)
 */
class OrderHistoryNote extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes, ModelReader;

    const CREATED_AT = 't_stamp';
    const UPDATED_AT = null;

    /**
     * @var int
     */
    protected $perPage = 100;

    /**
     * @var string
     */
    protected $table = 'orders_history';

    /**
     * @var string
     */
    protected $primaryKey = 'hId';

    /**
     * @var array
     */
    protected $visible = [
        'order_id',
        'message',
        'type_name',
        'author',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 'hID',
        'order_id'   => 'orders_id',
        'message'    => 'status',
        'type_name'  => 'type',
        'user_id'    => 'user',
        'author'     => 'user',
        'created_at' => 't_stamp',
        'is_deleted' => 'deleted',
    ];

    /**
     * @var array
     */
    protected $dates = [
        't_stamp',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'order_id',
        'message',
        'user_id',
        'created_at',
        'author',
        'type_name',
    ];

    protected $fillable = [
        'order_id',
        'message',
        'type_name',
        'author',
        'user_id',
        'type',
        'status',
        'campaign_id',
        'created_at',
    ];

    protected $attributes = [
        'user' => User::SYSTEM,
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'orders_id');
    }

    public function author()
    {
        return $this->hasOne(User::class, 'admin_id', 'user');
    }

    public function getAuthorAttribute()
    {
        return $this->author()->first()->makeHidden([
            'call_center_provider_id',
            'department_id',
        ]);
    }

    public function type_name()
    {
        return $this->hasOne(OrderHistoryNoteType::class, 'type_id', 'type');
    }

    public function getTypeNameAttribute()
    {
        return $this->type_name()->first()->name;
    }

    public function getMessageAttribute()
    {
        return (new \history_note_hook(
            $this->attributes['orders_id'],
            $this->attributes['type'],
            $this->attributes['status']
        ))->format_note();
    }

    /**
     * @return bool
     */
    public function getActiveColumn()
    {
        return false;
    }

    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public function scopeForOrder(Builder $query, $order_id)
    {
        return $query->where('orders_id', $order_id);
    }
}
