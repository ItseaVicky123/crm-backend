<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class ZiriPay
 * @package App\Models\Payment\Transaction
 */
class ZiriPay extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_ziripay';

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
