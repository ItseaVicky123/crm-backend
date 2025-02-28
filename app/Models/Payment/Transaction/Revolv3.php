<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;

/**
 * Class Revolv3
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $customer_id
 * @property int    $order_id
 * @property string $response_text
 * @property string $response_status
 * @property string $transaction_id
 * @property string $network_transaction_id
 * @property string $transaction_type
 */
class Revolv3 extends BaseModel
{
    use ForOrderScope;

    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_revolv3';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
        'network_transaction_id',
        'customer_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'order_id'               => 'orderId',
        'response_text'          => 'responseText',
        'transaction_id'         => 'transactionId',
        'network_transaction_id' => 'networkTransactionId',
    ];
}
