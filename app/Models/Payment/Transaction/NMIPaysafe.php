<?php

namespace App\Models\Payment\Transaction;

use App\Models\Payment\Transaction;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class NMIPaysafe
 * @package App\Models\Payment\Transaction
 */
class NMIPaysafe extends Transaction
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_nmi_paysafe';

    /**
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'order_id',
        'response_text',
        'transaction_id',
        'consent_id',
        'processor_id',
        'customer_vault_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'transaction_id' => 'transactionId',
        'response_text'  => 'responseText',
        'processor_id'   => 'processorId',
    ];
}
