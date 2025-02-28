<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class DLocal
 *
 * @package App\Models\Payment\Transaction
 *
 * @property int    $order_id              Order ID
 * @property string $transaction_id        Transaction ID
 * @property string $transaction_type      Transaction type
 * @property string $response_code         Response code
 * @property string $response_status       Status received after each request
 * @property string $response_text         Message received from the payment gateway
 * @property string $card_transaction_type Card transaction type
 * @method static Builder forOrder(int $orderId);
 */
class DLocal extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_dlocal';

    /**
     * @var array
     */
    protected $fillable = [
        'card_transaction_type',
        'order_id',
        'response_code',
        'response_status',
        'response_text',
        'transaction_id',
        'transaction_type',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'card_transaction_type' => 'cardTransactionType',
        'created_at'            => self::CREATED_AT,
        'order_id'              => 'orderId',
        'response_code'         => 'responseCode',
        'response_status'       => 'responseStatus',
        'response_text'         => 'responseText',
        'transaction_id'        => 'transactionId',
        'transaction_type'      => 'transactionType',
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
}
