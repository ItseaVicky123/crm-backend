<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class iBill
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $order_id        Transaction Order ID
 * @property string $response_code   Transaction Response Code
 * @property string $response_text   Transaction Response Text
 * @property string $transaction_id  Transaction ID
 */
class iBill extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_ibill';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'response_code',
        'response_text',
        'transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'response_text'  => 'responseText',
        'transaction_id' => 'transactionId',
    ];

    /**
     * @param Builder $query
     * @param int     $orderId
     * @return Builder
     */
    public function scopeForOrder($query, $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }
}
