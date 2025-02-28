<?php

namespace App\Models\Payment\Transaction;

use App\Models\Payment\Transaction;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class StackPay
 * @package App\Models\Payment\Transaction
 */
class StackPay extends Transaction
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';

    /**
     * @var string
     */
    public $table = 'gateway_transactions_stackpay';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'transaction_id' => 'transactionId',
        'response_text'  => 'responseText',
    ];
}
