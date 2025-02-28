<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SubscriptionHoldType
 * @package App\Models
 */
class SubscriptionHoldType extends Model
{

    // Hold type IDs
    //
    const SYSTEM          = 1;
    const USER            = 2;
    const HARD_DECLINE    = 3;
    const DECLINE_SALVAGE = 4;
    const CHARGEBACK      = 5;
    const MERCHANT        = 6;
    const OFFER           = 7;
    const ACCOUNT_UPDATER = 8;
    const INITIAL_DUNNING = 9;

    // These hold type IDs are used for collection subscription when we complete or pause/stop subscription
    public const COMPLETE = 10;
    public const CANCEL   = 11;

    const TYPES           = [
        self::SYSTEM          => 'system',
        self::USER            => 'user',
        self::HARD_DECLINE    => 'hard_decline',
        self::DECLINE_SALVAGE => 'decline_salvage',
        self::CHARGEBACK      => 'chargeback',
        self::MERCHANT        => 'merchant',
        self::OFFER           => 'offer',
        self::ACCOUNT_UPDATER => 'account_updater',
        self::INITIAL_DUNNING => 'initial_dunning_decline_salvage',
        self::COMPLETE        => 'complete',
        self::CANCEL          => 'cancel',
    ];

    /**
     * @var string
     */
    protected $table = 'vlkp_order_hold_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
    ];

    public function getSystemHoldTypes()
    {
        return [
            self::DECLINE_SALVAGE,
            self::HARD_DECLINE,
            self::INITIAL_DUNNING,
        ];
    }
}
