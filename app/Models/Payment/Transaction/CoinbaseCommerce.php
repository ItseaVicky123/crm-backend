<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Model;

class CoinbaseCommerce extends Model
{
    /**
     * @var string
     */
    protected $table = 'gateway_transactions_coinbase';

    /**
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'transaction_id',
        'response_text',
        'charge_code',
        'charge_id',
        'network',
        'status',
    ];
}
