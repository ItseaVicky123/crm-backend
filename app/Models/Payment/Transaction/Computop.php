<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Computop
 * @package App\Models\Payment\Transaction
 */
class Computop extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_computop';

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

    /**
     * @param Builder $query
     * @param int     $orderId
     * @return Builder
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
