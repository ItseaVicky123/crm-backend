<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class OrderProvider
 * @package App\Models
 */
class OrderProvider extends Model
{
    use Eloquence;

    /**
     * @var array
     */
    protected $visible = [
        'order_id',
        'profile_id',
        'provider_model',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string[]
     */
    protected $guarded = [
        'id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'order_id');
    }

    /**
     * @return mixed
     */
    public function profile()
    {
        return $this->morphTo('profile', 'profile_model', 'profile_id');
    }
}
