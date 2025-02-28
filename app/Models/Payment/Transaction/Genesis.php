<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Genesis extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_genesis';

    /**
     * @var array
     */
    protected $fillable = [
        'mode',
        'order_id',
        'rebill_token',
        'response_text',
        'status',
        'transaction_id',
        'unique_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'response_text'  => 'responseText',
        'transaction_id' => 'transactionId',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $orderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }
}
