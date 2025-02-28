<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;

/**
 * Class Reach
 *
 * @package App\Models\Payment\Transaction
 *
 * @property int $order_id
 * @property string $response_text
 * @property string $response_status
 * @property string $transaction_id
 * @property string $transaction_type
 */
class Reach extends BaseModel
{
    use ForOrderScope;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_reach';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
        'transaction_status',
        'reference_id'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'order_id'           => 'orderId',
        'response_text'      => 'responseText',
        'transaction_id'     => 'transactionId',
        'response_status'    => 'responseStatus',
        'transaction_type'   => 'transactionType',
        'transaction_status' => 'transactionStatus',
        'reference_id'       => 'referenceId',
    ];
}
