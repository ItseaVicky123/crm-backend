<?php

namespace App\Models\Payment\Transaction;

use App\Models\Payment\Transaction;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Square
 * @package App\Models\Payment\Transaction
 */
class Square extends Transaction
{
    use Eloquence, Mappable;

    const REFUND  = 'refund';
    const VOID    = 'void';
    const CAPTURE = 'capture';
    const AUTH    = 'auth';
    const SALE    = 'transaction';

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_square';

    /**
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'location_id',
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
