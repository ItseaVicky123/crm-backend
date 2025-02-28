<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SolidGate extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_solidgate';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
        'verify_url',
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
        'verify_url'       => 'verifyUrl',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $orderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }
}
