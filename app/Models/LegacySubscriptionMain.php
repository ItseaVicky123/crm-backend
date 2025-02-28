<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LegacySubscriptionMain
 *
 * Reader for the v_subscriptions_main view, uses slave connection.
 *
 * @package App\Models
 */

class LegacySubscriptionMain extends LegacySubscription
{
    /**
     * @var string
     */
    protected $table = 'v_subscriptions_main';
}
