<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @method static forOrder(int $orderId, int $param)
 */
class ThreeDVerify extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = null;
    const INITIAL_PROTECTION = 1;
    const FIRST_REBILL_PROTECTION = 2;
    const BOTH_PROTECTION = 3;
    const CARD_AUTH_A = 'attempted';
    const CARD_AUTH_V = 'verified';

    /**
     * @var string
     */
    protected $table = 'gateway_transactions_limelight_3d_verify';

    /**
     * @var array
     */
    protected $fillable = [
        'orderId',
        'transactionId',
        'responseText',
        'cavv',
        'eci',
        'xid',
        'version',
        'status',
        'ds_trans_id',
        'acs_trans_id',
        'protection_type_id'
    ];

    /**
     * @var array
     */
    protected array $maps = [
        'created_at'     => self::CREATED_AT,
        'order_id'       => 'orderId',
        'transaction_id' => 'transactionId',
        'response_text'  => 'responseText',
    ];

    public static array $eci_map         = [
        '01' => self::CARD_AUTH_A, // Mastercard
        '02' => self::CARD_AUTH_V, // Mastercard
        '05' => self::CARD_AUTH_V, // Visa
        '06' => self::CARD_AUTH_A  // Visa
    ];

    /**
     * @param Builder $query
     * @param int     $orderId
     * @param int     $protectionTypeId
     * @return Builder
     */
    public function scopeForOrder($query, $orderId, $protectionTypeId = null): ?Builder
    {
        if ($protectionTypeId) {
            return $query->where('order_id', $orderId)->where('protection_type_id', $protectionTypeId);
        }

        return $query->where('order_id', $orderId);
    }

    /**
     * @param bool $isSubscription
     * @param int $rebillDepth
     * @param int $protectionType
     * @return bool
     */
    public static function shouldSend($isSubscription, $rebillDepth, $protectionType): bool
    {
        if ($isSubscription) {
            if (
                ($protectionType === self::BOTH_PROTECTION) ||
                ($rebillDepth === 1 && $protectionType === self::FIRST_REBILL_PROTECTION) ||
                ($rebillDepth === 0 && $protectionType === self::INITIAL_PROTECTION)
            ) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * @return mixed|string|null
     */
    public function getCardholderAuthAttribute()
    {
        if ($this->eci && in_array($this->eci, array_keys(self::$eci_map))) {
            return self::$eci_map[$this->eci];
        }

        return null;
    }
}
