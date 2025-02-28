<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class PayPal V2
 *
 * @package App\Models\Payment\Transaction
 *
 * @property int    $order_id
 * @property string $paypal_auth_id
 * @property string $paypal_capture_id
 * @property string $paypal_order_id
 * @property string $paypal_request_id
 * @property string $response_text
 * @property string $transaction_id
 */
class PayPalV2 extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_paypal_v2';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'paypal_auth_id',
        'paypal_capture_id',
        'paypal_order_id',
        'paypal_request_id',
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