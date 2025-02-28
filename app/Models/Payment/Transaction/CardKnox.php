<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;
use Illuminate\Database\Eloquent\Builder;

class CardKnox extends BaseModel
{
    use ForOrderScope;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_cardknox';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'       => self::CREATED_AT,
        'order_id'         => 'orderId',
        'response_text'    => 'responseText',
        'response_status'  => 'responseStatus',
        'transaction_id'   => 'transactionId',
        'transaction_type' => 'transactionType',
    ];

}

