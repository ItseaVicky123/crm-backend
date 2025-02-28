<?php

namespace App\Models\Provider;

use App\Models\GatewayAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PaymentType extends Model
{
    protected $table = 'payment_type';

    /**
     * This is card type array where key is actual card type
     * which is using in our system and value is new name
     * which we use for email token for display purposes only.
     **/
    public const CARD_TYPES = [
        'visa'      => 'Visa',
        'master'    => 'MasterCard',
        'amex'      => 'AMEX',
        'discover'  => 'Discover',
        'square'    => 'Square',
        'paypal'    => 'PayPal',
        'applepay'  => 'Apple Pay',
        'googlepay' => 'Google Pay',
        'amazonpay' => 'AmazonPay',
    ];

    /**
     * Get the card type to human-readable format
     *
     * @param $key
     * @return string
     */
    public static function CardTypeHumanize($key)
    {
        $returnData = "";

        // Check if name not exist in array then return exactly same value passed.
        if (array_key_exists($key, self::CARD_TYPES)) {
            $returnData = self::CARD_TYPES[$key];
        } else {
            Log::debug(__METHOD__." New type ({$key}) was added to the system but not to the mapping. The payment type needs to be added to the mapping. Please create a ticket for it");
            $returnData = $key;
        }

        return $returnData;
    }

    public function scopeForGatewayAccount(Builder $query, GatewayAccount $account)
    {
        return $query->whereIn('gateway_account_id', [0, $account->id])
            ->where('group_id', 1);
    }
}
