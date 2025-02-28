<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    public const STATUS_INVALID  = 0;
    public const STATUS_NEW      = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_ARCHIVED = 3;
    public const STATUS_REFUNDED = 6;
    public const STATUS_VOID     = 6;
    public const STATUS_DECLINED = 7;
    public const STATUS_SHIPPED  = 8;
    public const STATUS_HOLD     = 9;
    public const STATUS_AWAITING = 10;
    public const STATUS_PENDING  = 11;

    public const STR_STATUS_VOIDED  = 'voided';
    public const STR_STATUS_REFUND  = 'fully refunded';
    public const STR_STATUS_SHIPPED = 'shipped';

    public const RMA_STATUS_SET      = 1;
    public const RMA_STATUS_RETURNED = 2;

    public const STR_STATUS_MAPPING = [
        self::STATUS_INVALID  => 'invalid',
        self::STATUS_NEW      => 'new',
        self::STATUS_APPROVED => 'approved',
        self::STATUS_ARCHIVED => 'archived',
        self::STATUS_VOID     => self::STR_STATUS_VOIDED,
        self::STATUS_DECLINED => 'declined',
        self::STATUS_SHIPPED  => self::STR_STATUS_SHIPPED,
        self::STATUS_HOLD     => 'hold',
        self::STATUS_AWAITING => 'awaiting',
        self::STATUS_PENDING  => 'pending',
    ];

    /**
     * @var string
     */
    protected $table = 'orders_status';

    /**
     * Get the status text based on the status id.
     *
     * @param int $statusId
     * @return string|null
     */
    public static function getStatusTextById(int $statusId): ?string
    {
        return self::STR_STATUS_MAPPING[$statusId] ?? null;
    }
}
