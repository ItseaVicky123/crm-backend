<?php

namespace App\Models\Payment\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Pathly extends BaseModel
{
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_pathly';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_text',
        'response_status',
        'transaction_type',
        'transaction_id',
        'customer_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'order_id'          => 'orderId',
        'response_text'     => 'responseText',
        'transaction_id'    => 'transactionId',
    ];

    /**
     * @param Builder $query
     * @param int $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }
}
