<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Skrill extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';

    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_skrill';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'payment_type',
        'transaction_id',
        'rec_payment_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'      => self::CREATED_AT,
        'order_id'        => 'orderId',
        'response_text'   => 'responseText',
        'response_status' => 'responseStatus',
        'payment_type'    => 'paymentType',
        'transaction_id'  => 'transactionId',
        'rec_payment_id'  => 'recPaymentId',
    ];

    /**
     * @param Builder $query
     * @param int $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }
}
