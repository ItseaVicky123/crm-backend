<?php

namespace App\Models;

use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class KountTransaction
 * @package App\Models
 */
class KountTransaction extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'fraudprovider_transactions_kount';

    /**
     * @var string
     */
    protected $primaryKey = 'gatewayTransactionsKountId';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'transaction_id',
        'order_id',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'             => 'gatewayTransactionsKountId',
        'transaction_id' => 'TRAN',
        'order_id'       => 'orderId',
        'session_id'     => 'SESS',
        'created_at'     => self::CREATED_AT,
        'mode'           => 'MODE',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'transaction_id',
        'order_id',
        'created_at',
    ];

    protected $guarded = [
        'id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order()
    {
        return $this->hasOne(Order::class, 'orders_id', 'orderId');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $orderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrder(Builder $query, $orderId)
    {
        return $query->where('orderId', $orderId);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $orderId
     * @return mixed
     */
    public function scopeChargedback(Builder $query, $orderId)
    {
        return $query->forOrder($orderId)
            ->where('MODE', 'C');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $orderId
     * @return mixed
     */
    public function scopeWithoutFeedback(Builder $query, $orderId)
    {
        return $query->forOrder($orderId)
            ->whereNotIn('MODE', ['C', 'R']);
    }
}
