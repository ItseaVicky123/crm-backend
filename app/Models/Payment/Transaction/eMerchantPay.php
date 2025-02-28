<?php

namespace App\Models\Payment\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class eMerchantPay
 *
 * @package App\Models\Payment\Transaction
 *
 * @property string $auth_code        Transaction Authorization Code
 * @property string $cavv             3D Secure Transaction cavv Parameter
 * @property string $eci              3D Secure Transaction eci Parameter
 * @property string $em_order_id      eMerchantPay-specific Order ID
 * @property string $item_id          Transaction Item ID
 * @property string $order_id         Transaction Order ID
 * @property string $response         Transaction Response
 * @property string $response_code    Transaction Response Code
 * @property string $response_text    Transaction Response Text
 * @property string $scheme_reference Credential on File Scheme Reference
 * @property string $sticky_order_id  sticky Order ID
 * @property string $transaction_id   Transaction ID
 * @property string $xid              3D Secure Transaction xid Parameter
 */
class eMerchantPay extends Model
{
    use Eloquence, Mappable;

    public const CREATED_AT = 'createdOn';
    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_emerchantpay';

    /**
     * @var array
     */
    protected $fillable = [
        'authCode',
        'cavv',
        'eci',
        'item_id',
        'order_id',
        'orderId',
        'response',
        'responseCode',
        'scheme_reference',
        'xid',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'auth_code'       => 'authCode',
        'created_at'      => self::CREATED_AT,
        'em_order_id'     => 'order_id',
        'sticky_order_id' => 'orderId',
        'response_code'   => 'responseCode',
        'response_text'   => 'responseText',
        'transaction_id'  => 'transactionId',
    ];

    /**
     * @param Builder $query
     * @param int $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('sticky_order_id', $orderId);
    }
}
