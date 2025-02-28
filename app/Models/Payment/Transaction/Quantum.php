<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;

/**
 * Class Quantum
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $approval_code
 * @property int    $order_id
 * @property string $response_text
 * @property string $response_status
 * @property string $transaction_id
 * @property string $transaction_type
 * @property string $transaction_identifier
 * @property mixed|string $payment_id
 * @property mixed|string $alt_transaction_id
 */
class Quantum extends BaseModel
{
    use ForOrderScope;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_quantum';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
        'approval_code',
        'transaction_identifier',
        'payment_id',
        'alt_transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'order_id'         => 'orderId',
        'response_text'    => 'responseText',
        'transaction_id'   => 'transactionId',
        'response_status'  => 'responseStatus',
        'transaction_type' => 'transactionType',
    ];
}
