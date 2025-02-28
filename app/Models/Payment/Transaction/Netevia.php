<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;

/**
 * Class Netevia
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $customer_id
 * @property int    $order_id
 * @property string $response_text
 * @property string $response_status
 * @property string $transaction_id
 * @property string $transaction_type
 */
class Netevia extends BaseModel
{
    use ForOrderScope;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_netevia';

    /**
     * @var array
     */
    protected $fillable = [
        'auth_code',
        'order_id',
        'response_code',
        'response_text',
        'transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'auth_code'      => 'authCode',
        'order_id'       => 'orderId',
        'response_code'  => 'responseCode',
        'response_text'  => 'responseText',
        'transaction_id' => 'transactionId',
    ];
}
