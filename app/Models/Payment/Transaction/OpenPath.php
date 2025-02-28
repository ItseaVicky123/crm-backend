<?php

namespace App\Models\Payment\Transaction;

use App\Models\Payment\Transaction;

/**
 * Class OpenPath
 *
 * @package App\Models\Payment\Transaction
 */
class OpenPath extends Transaction
{

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_openpath';

    /**
     * @var array
     */
    protected $fillable = [
        'response',
        'orderId',
        'authCode',
        'transactionId',
        'avsResponse',
        'cvvResponse',
        'responseText',
        'responseCode',
        'processorId',
        'transactionType',
    ];

    /**
     * @var array
     */
    protected array $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'auth_code'      => 'authCode',
        'transaction_id' => 'transactionId',
        'avs_response'   => 'avsResponse',
        'cvv_response'   => 'cvvResponse',
        'response_text'  => 'responseText',
        'response_code'  => 'responseCode',
        'processor_id'   => 'processorId',
    ];

}
