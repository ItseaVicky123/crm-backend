<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Class CurrencyId
 * @package App\Models\OrderAttributes
 */
class CurrencyId extends OrderAttribute
{
    const TYPE_ID = 23;
    const IS_IMMUTABLE = true;
    const CUR_USD = 1;
    const DEFAULT_VALUE = self::CUR_USD;
    const IGNORE_DUPLICATES = true;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    public static function setByOrderId($orderId)
    {
        try {
            //In the case when order is deleted or order status < 2
            //Adding withoutGlobalScopes and remove findOrFail
            //to prevent fail and this error "No query results for model [App\Models\Order]"
            if ($order = Order::withoutGlobalScopes()->find($orderId)) {
                $currencyId = self::DEFAULT_VALUE;
                $gateway    = $order->gateway;

                if ($gateway && ($currency = $gateway->currency)) {
                    $currencyId = $currency->id;
                }

                parent::createForOrder($orderId, $currencyId);
            } else {
                \fileLogger::log_warning(__METHOD__." Order ID ($orderId) was not found");
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
