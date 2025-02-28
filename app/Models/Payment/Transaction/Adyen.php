<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Adyen extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_adyen';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'shopper_reference',
        'token',
        'transaction_status'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'         => self::CREATED_AT,
        'order_id'           => 'orderId',
        'response_text'      => 'responseText',
        'transaction_id'     => 'transactionId',
        'transaction_status' => 'transactionStatus',
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
