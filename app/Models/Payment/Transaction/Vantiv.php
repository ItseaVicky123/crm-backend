<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Vantiv
 * @package App\Models\Payment\Transaction
 */
class Vantiv extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_vantiv';

    /**
     * @var array
     */
    protected $fillable = [
        'auth_code',
        'authentication_result',
        'avs_result',
        'card_validation_result',
        'message',
        'order_id',
        'orders_id',
        'network_transaction_id',
        'post_date',
        'response_text',
        'transaction_id',
        'response',
        'response_time',
        'token',
        'token_expiration',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'                     => 'gatewayTransactionsLitleId',
        'created_at'             => self::CREATED_AT,
        'order_id'               => 'ordersId',
        'orders_id'              => 'ordersId',
        'transaction_id'         => 'litleTxnId',
        'response_text'          => 'responseText',
        'response_time'          => 'responseTime',
        'post_date'              => 'postDate',
        'auth_code'              => 'authCode',
        'avs_result'             => 'avsResult',
        'card_validation_result' => 'cardValidationResult',
        'authentication_result'  => 'authenticationResult',
    ];
}
