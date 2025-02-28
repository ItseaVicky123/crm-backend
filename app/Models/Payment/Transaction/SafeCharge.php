<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Model;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class SafeCharge
 * @package App\Models\Payment\Transaction
 */
class SafeCharge extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_safecharge';

    /**
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'sc_order_id',
        'transaction_id',
        'user_payment_id',
        'user_token_id',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'transaction_id' => 'transactionId',
        'response_text'  => 'responseText',
    ];
}
