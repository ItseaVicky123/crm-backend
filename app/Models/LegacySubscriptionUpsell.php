<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LegacySubscriptionMain
 *
 * Reader for the v_subscriptions_upsell view, uses slave connection.
 *
 * @package App\Models
 */

class LegacySubscriptionUpsell extends LegacySubscription
{
    /**
     * @var string
     */
    protected $table = 'v_subscriptions_upsell';
}
