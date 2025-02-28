<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Braintree Payments
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $braintree_customer_id Braintree Customer ID
 * @property string $order_id              Transaction Order ID
 * @property string $payment_method_id     Payment Method ID
 * @property string $response_text         Transaction Response Text
 * @property string $transaction_id        Transaction ID (UUID)
 */
class BraintreePayments extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_braintreepayments';

    /**
     * @var array
     */
    protected $fillable = [
        'braintree_customer_id',
        'created_at',
        'order_id',
        'payment_method_id',
        'response_text',
        'transaction_id',
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
