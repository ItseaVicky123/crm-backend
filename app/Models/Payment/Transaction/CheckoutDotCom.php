<?php

namespace App\Models\Payment\Transaction;

use App\Models\Payment\Transaction;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class CheckoutDotCom
 * @package App\Models\Payment\Transaction
 *
 * @property string $customer_id
 * @property int    $order_id
 * @property string $response_text
 * @property string $transaction_id
 * @property string $trans_type
 */
class CheckoutDotCom extends Transaction
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_checkout_2';

    /**
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'order_id',
        'response_text',
        'trans_type',
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
