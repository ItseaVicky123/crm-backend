<?php

namespace App\Lib;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Class SubscriptionHandler
 * @package App\Lib
 */
class SubscriptionHandler
{
    /**
     * @param \App\Models\Subscription $subscription
     * @return array
     */
    public function getNextProduct(Subscription $subscription)
    {
        //Check if it has a billing model
        $billingModelOrder = $subscription->order_subscription()->first();
        $orderProduct      = $subscription->order_product()->first();

        if ($orderProduct->next_bundle_products()->count()) {
            $nextOrderProductBundles = $orderProduct->next_bundle_products()->get();
            $nextBundleChildren      = [];

            foreach ($nextOrderProductBundles as $orderProductBundle) {
                $nextBundleChildren[] = [
                    'product_id' => $orderProductBundle->product_id,
                    'quantity'   => $orderProductBundle->quantity,
                    'price'      => $orderProductBundle->charged_price,
                ];
            }
        }

        if ($billingModelOrder && $nextProduct = $billingModelOrder->next_recurring_product()->first()) {
            $nextVariantId = $billingModelOrder->next_recurring_variant ?? 0;
            $nextProduct   = [
                'id'           => $billingModelOrder->next_recurring_product,
                'category'     => $nextProduct->category,
                'variant_id'   => $nextVariantId,
                'name'         => $nextProduct->name,
                'quantity'     => $billingModelOrder->next_recurring_quantity,
                'sku'          => $nextProduct->sku,
                'is_shippable' => $nextProduct->is_shippable,
                'children'     => $nextBundleChildren
            ];
        } else {
            //Fall back to order_product
            $nextVariantId = $orderProduct->variant_id ?? 0;
            $nextProduct   = [
                'id'           => $orderProduct->product_id,
                'category'     => $orderProduct->category,
                'variant_id'   => $nextVariantId,
                'name'         => $orderProduct->product_name,
                'quantity'     => $orderProduct->products_quantity,
                'sku'          => $orderProduct->sku,
                'is_shippable' => $orderProduct->is_shippable,
                'children'     => $nextBundleChildren
            ];
        }

        return $nextProduct;
    }

    /**
     * @param \App\Models\Subscription $subscription
     * @return mixed
     */
    public function getNextShippingMethod(Subscription $subscription)
    {
        $mainOrderModel = $subscription->isMain() ? $subscription : $subscription->main;

        return $mainOrderModel->shipping_method;
    }

    /**
     * @param \App\Models\Subscription $subscription
     * @return array
     */
    public function getNextShippingAddress(Subscription $subscription)
    {
        $override = $subscription->subscription_override;

        if ($override && $address = $override->address()->first()) {
            return [
                'first_name'   => $address->first_name,
                'last_name'    => $address->last_name,
                'address'      => $address->street,
                'address2'     => $address->street_2,
                'city'         => $address->city,
                'state'        => $address->state,
                'zip'          => $address->zip,
                'country'      => $address->country()->first()->name,
                'country_iso2' => $address->country,
            ];
        }

        return $subscription->shipping_array;
    }

    /**
     * @param \App\Models\Subscription $subscription
     * @return array
     */
    public function getNextBillingAddress(Subscription $subscription)
    {
        $override = $subscription->subscription_override;

        if ($override && $payment = $override->contact_payment_source()->first()) {
            return [
                'first_name'   => $payment->address->first_name,
                'last_name'    => $payment->address->last_name,
                'address'      => $payment->address->street,
                'address2'     => $payment->address->street_2,
                'city'         => $payment->address->city,
                'state'        => $payment->address->state,
                'zip'          => $payment->address->zip,
                'country'      => $payment->address->country()->first()->name,
                'country_iso2' => $payment->address->country,
            ];
        } else {
            $mainOrderModel = $subscription->isMain() ? $subscription : $subscription->main;

            return [
                'first_name'   => $mainOrderModel->bill_first_name,
                'last_name'    => $mainOrderModel->bill_last_name,
                'address'      => $mainOrderModel->bill_address,
                'address2'     => $mainOrderModel->bill_address2,
                'city'         => $mainOrderModel->bill_city,
                'state'        => $mainOrderModel->bill_state,
                'zip'          => $mainOrderModel->bill_zip,
                'country'      => $mainOrderModel->billing_country_name,
                'country_iso2' => $mainOrderModel->billing_country_iso2,
            ];
        }
    }

    /**
     * @param \App\Models\Subscription $subscription
     * @return mixed
     */
    public function getBillingModelId(Subscription $subscription)
    {
        if ($billingModelOrder = $subscription->order_subscription()->first()) {
            return $billingModelOrder->billing_model_id;
        }

        return '';
    }

    /**
     * @param \App\Models\Subscription $subscription
     * @return array
     */
    public function getNextRecurringDetails(Subscription $subscription)
    {
        $response = [
            'product'          => $this->getNextProduct($subscription),
            'shipping_method'  => $this->getNextShippingMethod($subscription),
            'shipping'         => $this->getNextShippingAddress($subscription),
            'payment_source'   => 'N/A',
            'billing'          => $this->getNextBillingAddress($subscription),
            'billing_model_id' => $this->getBillingModelId($subscription),
            'recurring_date'   => $subscription->next_valid_recurring_date,
        ];

        $override = $subscription->subscription_override;
        if ($override && $payment = $override->contact_payment_source()->first()) {
            $response['payment_source'] = [
                'alias'    => $payment->alias,
                'first_6'  => $payment->first_6,
                'last_4'   => $payment->last_4,
                'exp_date' => $payment->expiry,
            ];
        }

        return $response;
    }
}
