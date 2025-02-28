<?php

namespace App\Lib\Orders\OrderTotals\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderTotalRequest
 * @package App\Lib\Orders\OrderTotals\ModuleRequests
 */
class OrderTotalRequest extends ModuleRequest
{
    /**
     * @var bool $isLegacyClient
     */
    protected bool $isLegacyClient = false;

    /**
     * OrderTotalRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->isLegacyClient = !$this->has('offers');

        if (!$this->isLegacyClient) {
            $this->handleValidation();
        } else {
            $this->handleLegacyValidation();
        }
    }

    /**
     * Is the client DNVB or legacy?
     * @return bool
     */
    public function isLegacyClient(): bool
    {
        return $this->isLegacyClient;
    }

    /**
     * Validation for DNVB Configuration instances.
     * @throws ValidationException
     */
    protected function handleValidation(): void
    {
        $rules      = [
            'campaign_id'                    => 'required|integer|exists:mysql_slave.campaigns,c_id',
            'shipping_id'                    => 'sometimes|integer|exists:mysql_slave.shipping,s_id',
            'offers'                         => 'required|array',
            'offers.*.id'                    => 'required|integer|exists:mysql_slave.billing_offer,id',
            'offers.*.product_id'            => 'required|integer|exists:mysql_slave.products,products_id',
            'offers.*.billing_model_id'      => 'required|integer|exists:mysql_slave.billing_frequency,id',
            'offers.*.variant_id'            => 'sometimes|integer|exists:mysql_slave.product_variant,id',
            'offers.*.price'                 => 'sometimes|money',
            'offers.*.quantity'              => 'required|integer|min:1',
            'offers.*.cycles'                => 'sometimes|integer',
            'offers.*.cycle_depth'           => 'sometimes|integer',
            'offers.*.trial'                 => 'sometimes|boolean',
            'offers.*.use_workflow'          => 'sometimes|boolean',
            'offers.*.trial_workflow_id'     => 'sometimes|int|exists:mysql_slave.trial_workflows,id',
            'offers.*.children'              => 'sometimes|array',
            'offers.*.children.*.product_id' => 'required_with:offers.*.children|integer|exists:mysql_slave.products,products_id',
            'offers.*.children.*.quantity'   => 'required_with:offers.*.children|integer|min:1',
            'location'                       => 'sometimes|required|array',
            'location.country'               => 'sometimes|required',
            'location.postal_code'           => 'sometimes|required',
            'location.state'                 => 'sometimes',
            'billingLocation'                => 'sometimes|required|array',
            'billingLocation.country'        => 'sometimes|required',
            'billingLocation.postal_code'    => 'sometimes|required',
            'billingLocation.state'          => 'sometimes',
            'promo'                          => 'sometimes|array',
            'promo.code'                     => 'required_with:promo',
            'promo.email'                    => 'required_with:promo',
            'volume_discount_id'             => 'integer|exists:mysql_slave.volume_discounts,id',
        ];
        $attributes = [
            'campaign_id'                    => 'Campaign ID',
            'shipping_id'                    => 'Shipping Method ID',
            'offers'                         => 'Offers',
            'offers.*.id'                    => 'Offer ID',
            'offers.*.product_id'            => 'Product ID',
            'offers.*.billing_model_id'      => 'Billing Model ID',
            'offers.*.variant_id'            => 'Variant ID',
            'offers.*.price'                 => 'Price',
            'offers.*.quantity'              => 'Quantity',
            'offers.*.cycles'                => 'Prepaid Cycles',
            'offers.*.cycle_depth'           => 'Cycle Depth',
            'offers.*.trial'                 => 'Trial Flag',
            'offers.*.children'              => 'Bundle Product Children',
            'offers.*.children.*.product_id' => 'Bundle Child Product ID',
            'offers.*.children.*.quantity'   => 'Bundle Child Product Quantity',
            'location'                       => 'Shipping Location',
            'location.country'               => 'Shipping Country',
            'location.postal_code'           => 'Shipping Postal Code',
            'location.state'                 => 'Shipping State',
            'billingLocation'                => 'Shipping Location',
            'billingLocation.country'        => 'Shipping Country',
            'billingLocation.postal_code'    => 'Shipping Postal Code',
            'billingLocation.state'          => 'Shipping State',
            'promo'                          => 'Promo Code Data',
            'promo.code'                     => 'Promo Code',
            'promo.email'                    => 'Promo Code Email',
            'volume_discount_id'             => 'Volume Discount ID',
        ];
        $messages   = [
            'offers.*.id.required'                         => ':attribute is required',
            'offers.*.id.integer'                          => ':attribute must be an integer',
            'offers.*.id.exists'                           => ':attribute is invalid',
            'offers.*.product_id.required'                 => ':attribute is required',
            'offers.*.product_id.integer'                  => ':attribute must be an integer',
            'offers.*.product_id.exists'                   => ':attribute is invalid',
            'offers.*.billing_model_id.required'           => ':attribute is required',
            'offers.*.billing_model_id.integer'            => ':attribute must be an integer',
            'offers.*.billing_model_id.exists'             => ':attribute is invalid',
            'offers.*.variant_id.integer'                  => ':attribute must be an integer',
            'offers.*.variant_id.exists'                   => ':attribute is invalid',
            'offers.*.price.regex'                         => ':attribute must be a decimal number',
            'offers.*.quantity.integer'                    => ':attribute must be an integer',
            'offers.*.quantity.min'                        => ':attribute must be at least 1',
            'offers.*.trial.boolean'                       => ':attribute must be boolean',
            'offers.*.children.*.product_id.required_with' => ':attribute is required',
            'offers.*.children.*.product_id.integer'       => ':attribute must be an integer',
            'offers.*.children.*.product_id.exists'        => ':attribute is invalid',
            'offers.*.children.*.quantity.required_with'   => ':attribute is required',
            'offers.*.children.*.quantity.integer'         => ':attribute must be an integer',
            'offers.*.children.*.quantity.min'             => ':attribute must be at least 1',
            'location.country.required'                    => ':attribute is required',
            'location.postal_code.required'                => ':attribute is required',
            'billingLocation.country.required'             => ':attribute is required',
            'billingLocation.postal_code.required'         => ':attribute is required',
            'promo.code.required_with'                     => ':attribute is required',
            'promo.email.required_with'                    => ':attribute is required to validate the promo code',
            'volume_discount_id.integer'                   => ':attribute must be an integer',
            'volume_discount_id.exists'                    => ':attribute is invalid',
        ];
        $this->validate($rules, $attributes, $messages);
    }

    /**
     * Validation for Legacy instances, not using Billing models and offers.
     * @throws ValidationException
     */
    protected function handleLegacyValidation(): void
    {
        $rules      = [
            'campaign_id'                 => 'required|integer|exists:mysql_slave.campaigns,c_id',
            'shipping_id'                 => 'sometimes|integer|exists:mysql_slave.shipping,s_id',
            'products'                    => 'required|array',
            'products.*.id'               => 'distinct|required|integer|exists:mysql_slave.products,products_id',
            'products.*.variant_id'       => 'sometimes|integer|exists:mysql_slave.product_variant,id',
            'products.*.price'            => 'sometimes|money',
            'products.*.quantity'         => 'required|integer|min:1',
            'location'                    => 'required|array',
            'location.country'            => 'required',
            'location.postal_code'        => 'required',
            'location.state'              => 'sometimes',
            'billingLocation'             => 'sometimes|required|array',
            'billingLocation.country'     => 'sometimes|required',
            'billingLocation.postal_code' => 'sometimes|required',
            'billingLocation.state'       => 'sometimes',
            'promo'                       => 'sometimes|array',
            'promo.code'                  => 'required_with:promo',
            'promo.email'                 => 'required_with:promo',
        ];
        $attributes = [
            'campaign_id'                 => 'Campaign ID',
            'shipping_id'                 => 'Shipping Method ID',
            'products'                    => 'Products',
            'products.*.id'               => 'Product ID',
            'products.*.variant_id'       => 'Variant ID',
            'products.*.price'            => 'Price',
            'products.*.quantity'         => 'Quantity',
            'location'                    => 'Shipping Location',
            'location.country'            => 'Shipping Country',
            'location.postal_code'        => 'Shipping Postal COde',
            'location.state'              => 'Shipping State/Region',
            'billingLocation'             => 'Shipping Location',
            'billingLocation.country'     => 'Shipping Country',
            'billingLocation.postal_code' => 'Shipping Postal Code',
            'billingLocation.state'       => 'Shipping State',
            'promo'                       => 'Promo Code Data',
            'promo.code'                  => 'Promo Code',
            'promo.email'                 => 'Promo Code Email',
        ];
        $messages   = [
            'products.*.id.distinct'               => ':attribute must be distinct',
            'products.*.id.required'               => ':attribute is required',
            'products.*.id.integer'                => ':attribute must be an integer',
            'products.*.id.exists'                 => ':attribute is invalid',
            'products.*.variant_id.integer'        => ':attribute must be an integer',
            'products.*.variant_id.exists'         => ':attribute is invalid',
            'products.*.price.regex'               => ':attribute must be a decimal number',
            'products.*.quantity.integer'          => ':attribute must be an integer',
            'products.*.quantity.min'              => ':attribute must be at least 1',
            'location.country.required'            => ':attribute is required',
            'location.postal_code.required'        => ':attribute is required',
            'billingLocation.country.required'     => ':attribute is required',
            'billingLocation.postal_code.required' => ':attribute is required',
            'promo.code.required_with'             => ':attribute is required',
            'promo.email.required_with'            => ':attribute is required to validate the promo code',
        ];
        $this->validate($rules, $attributes, $messages);
    }
}
