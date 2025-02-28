<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class CardFlo
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $capture_id     Transaction Capture ID
 * @property string $order_id       Transaction Order ID
 * @property string $purchase_id    Transaction Purchase ID
 * @property string $response_text  Transaction Response Text
 * @property string $transaction_id Transaction ID (UUID)
 */
class CardFlo extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_cardflo';

    /**
     * @var array
     */
    protected $fillable = [
        'capture_id',
        'order_id',
        'purchase_id',
        'response_text',
        'transaction_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at'     => self::CREATED_AT,
        'capture_id'     => 'capture_id',
        'order_id'       => 'orderId',
        'purchase_id'    => 'purchase_id',
        'response_text'  => 'responseText',
        'transaction_id' => 'transactionId',
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
