<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class PayEngine
 * @package App\Models\Payment\Transaction
 */
class PayEngine extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_pay_engine';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'transaction_id',
        'address_id',
        'customer_id',
        'pe_order_id',
        'persona_id',
        'cof_contract_id',
        'scheme_trace_id'
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
