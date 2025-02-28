<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ThreeDS
 * @package App\Models\Payment
 */
class ThreeDS extends Model
{
    public const CREATED_AT                  = 'date_in';
    public const UPDATED_AT                  = null;
    public const PROCESS_STATUS_START        = 0;
    public const PROCESS_STATUS_AUTH_MODE    = 1;
    public const PROCESS_STATUS_CAPTURE_MODE = 2;

    /**
     * @var string
     */
    public $table = 'three_d_secure';

    /**
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'content',
        'process_status',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'created_at' => self::CREATED_AT,
        'order_id'   => 'orders_id',
    ];
}
