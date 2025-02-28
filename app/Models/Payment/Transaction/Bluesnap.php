<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Bluesnap
 * @package App\Models\Payment\Transaction
 */
class Bluesnap extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_bluesnap';

    /**
     * @var array
     */
    protected $fillable = [
        'card_transaction_type',
        'order_id',
        'response_code',
        'response_text',
        'transaction_id',
        'transaction_type',
        'original_network_transaction_id',
        'network_transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'card_transaction_type'           => 'cardTransactionType',
        'created_at'                      => self::CREATED_AT,
        'order_id'                        => 'orderId',
        'response_code'                   => 'responseCode',
        'response_text'                   => 'responseText',
        'transaction_id'                  => 'transactionId',
        'transaction_type'                => 'transactionType',
        'original_network_transaction_id' => 'originalNetworkTransactionId',
        'network_transaction_id'          => 'networkTransactionId',
    ];

    /**
     * @param Builder $query
     * @param int     $orderId
     * @return Builder
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
