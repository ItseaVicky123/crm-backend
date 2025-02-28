<?php

namespace App\Lib\Orders\SubscriptionOrders\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;

/**
 * Class UpdateRequest
 * @package App\Lib\Orders\SubscriptionOrders\ModuleRequests
 */
class UpdateRequest extends ModuleRequest
{
    /**
     * @param array $additionalRules
     * @return array
     */
    protected function getBaseRules(array $additionalRules = []): array
    {
        return array_merge([
            'order_id'        => 'required_without:subscription_id|int|exists:mysql_slave.orders,orders_id',
            'product_id'      => 'exists:mysql_slave.products,products_id',
            'variant_id'      => 'exists:mysql_slave.product_variant,id',
            'subscription_id' => 'required_without:order_id|regex:~^[a-f0-9]{32}$~',
        ], $additionalRules);
    }

    /**
     * @param array $additionalAttributes
     * @return array
     */
    protected function getBaseAttributes(array $additionalAttributes = []): array
    {
        return array_merge([
            'order_id'        => 'Order ID',
            'product_id'      => 'Product ID',
            'variant_id'      => 'Product Variant ID',
            'subscription_id' => 'Subscription ID',
        ], $additionalAttributes);
    }
}
