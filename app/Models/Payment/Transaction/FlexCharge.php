<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class FlexCharge
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $flexcharge_order_id
 * @property int    $order_id
 * @property string $order_session_key
 * @property string $response_text
 * @property string $card_token
 * @property string $status
 * @property string $transaction_id
 * @property string $transmit_id
 */
class FlexCharge extends BaseModel
{
    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_flexcharge';

    /**
     * @var array
     */
    protected $fillable = [
        'created_at',
        'flexcharge_order_id',
        'order_id',
        'order_session_key',
        'response_text',
        'card_token',
        'transaction_id',
        'transmit_id',
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
     * @param Builder $query
     * @param int     $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeRefundedTransactions(Builder $query, string $transactionId): Builder
    {
        return $query->where('response_text', 'REFUNDED')->where('transaction_id', $transactionId);
    }
}
