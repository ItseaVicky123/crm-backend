<?php

namespace App\Lib\Campaigns;

use App\Events\Campaign\CampaignCreated;
use App\Models\Campaign\AlternativePayment;
use App\Models\Campaign\CampaignProvider;
use App\Models\Campaign\Field\Option\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Campaign\Campaign;
use App\Models\Country;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Main handler for copying a campaign.
 * Class CopyHandler
 * @package App\Lib\Campaigns
 */
class CopyHandler
{
    /**
     * @var Campaign|null $copy
     */
    protected ?Campaign $copy = null;

    /**
     * @var int|null $newCampaignId
     */
    protected ?int $newCampaignId = null;

    /**
     * @var Campaign|null $original
     */
    protected ?Campaign $original = null;

    /**
     * @var int|null $originalCampaignId
     */
    protected ?int $originalCampaignId = null;

    /**
     * CopyHandler constructor.
     * @param int $originalCampaignId
     *@throws Exception
     */
    public function __construct(int $originalCampaignId)
    {
        if (! ($this->originalCampaignId = $originalCampaignId)) {
            throw new Exception('Invalid campaign ID');
        }
    }

    /**
     * Perform the copy operation.
     * @return int
     * @throws Exception
     */
    public function performCopy(): int
    {
        $newCampaignId = 0;

        try {
            // Load up the target campaign instance
            //
            $this->original = Campaign::findOrFail($this->originalCampaignId);

            /**
             * Check campaign has any inactive country or not
             */
            $campaignCountryIds         = explode(',', $this->original->valid_countries);
            $campaignHasOldCountryCheck = Country::where('active', 0)->whereIn('id', $campaignCountryIds)->exists();

            if ($campaignHasOldCountryCheck) {

                throw new Exception('Sorry the campaign you are trying to copy has old countries please update them before trying to copy.');
            } else {

                // There are many pieces, make sure we roll back if things don't go as planned
                //
                DB::beginTransaction();

                if ($this->copy = $this->copyWithNoRelations()) {
                    $this->copyOffers();
                    $this->copyShipping();
                    $this->copyPaymentMethods();
                    $this->copyCountries();
                    $this->copyProviders();
                    $this->copyPaymentRouterConfigurations();
                    $this->copyPostbackProfiles();
                    $this->copyBinProfiles();
                    $this->copyCouponProfiles();
                    $this->copyReturnProfiles();
                    $this->copySalesTaxProfiles();
                    $this->copyLegacyCampaignProducts();
                    $newCampaignId = $this->newCampaignId;

                    try {
                        Event::dispatch(new CampaignCreated(Campaign::findOrFail($newCampaignId)));
                    } catch (Exception $exception) {
                        Log::critical('SWS critical - Events dispatch - Something went wrong while dispatching CampaignCreated event on performCopy', [
                            'exception'  => $exception->getMessage(),
                            'trace'      => $exception->getTraceAsString(),
                            'campaignId' => $newCampaignId,
                        ]);
                    }
                } else {
                    throw new Exception('Failed to copy campaign columns');
                }

                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $newCampaignId;
    }

    /**
     * Copy the base columns in the campaigns table to a new campaign record.
     * @return Campaign
     */
    private function copyWithNoRelations(): Campaign
    {
        $newCampaign = Campaign::create([
            'name'                              => $this->original->name . ' (Copy)',
            'description'                       => $this->original->description,
            'shipping_id'                       => $this->original->shipping_id,
            'gateway_id'                        => $this->original->gateway_id,
            'warehouse_id'                      => $this->original->warehouse_id,
            'alt_provider_id'                   => $this->original->alt_provider_id,
            'payment_router_id'                 => $this->original->lbc_id,
            'is_load_balanced'                  => $this->original->is_load_balanced,
            'max_rebill_amount_per_day'         => $this->original->max_rebill_amount_per_day,
            'enabled_max_rebill_amount_per_day' => $this->original->enabled_max_rebill_amount_per_day,
            'rebill_daily_amount'               => $this->original->rebill_daily_amount,
            'is_prepaid_blocked'                => $this->original->use_pre_paid,
            'is_custom_price_allowed'           => $this->original->allow_custom_pricing,
            'countries'                         => $this->original->valid_countries,
            'custom_products'                   => $this->original->custom_products,
            'is_linktrust_postback_pixel'       => $this->original->is_linktrust_postback_pixel,
            'linktrust_campaign_id'             => $this->original->linktrust_campaign_id,
            'fulfillment_id'                    => $this->original->fulfillment_id,
            'check_provider_id'                 => $this->original->check_provider_id,
            'membership_provider_id'            => $this->original->membership_provider_id,
            'tax_provider_id'                   => $this->original->tax_provider_id,
            'call_confirm_provider_id'          => $this->original->call_confirm_provider_id,
            'chargeback_provider_id'            => $this->original->chargeback_provider_id,
            'prospect_provider_id'              => $this->original->prospect_provider_id,
            'is_avs_enabled'                    => $this->original->is_avs_enabled,
            'email_provider_id'                 => $this->original->email_provider_id,
            'fraud_provider_id'                 => $this->original->fraud_provider_id,
            'pre_auth_amount'                   => $this->original->pre_auth_amount,
            'integration_type_id'               => $this->original->integration_type_id,
            'is_collections_enabled'            => $this->original->collections_flag,
            'data_verification_provider_id'     => $this->original->data_verification_provider_id,
            'channel_id'                        => $this->original->channel_id,
            'expense_profile_id'                => $this->original->expense_profile_id,
            'gateway_descriptor'                => $this->original->gateway_descriptor,
            'site_url'                          => $this->original->site_url,
            'is_store'                          => $this->original->is_store,
            'form_type'                         => $this->original->form_type,
            'prospect_list_id'                  => $this->original->prospect_list_id,
            'customer_list_id'                  => $this->original->customer_list_id,
            'max_grace_period'                  => $this->original->max_grace_period,
        ]);

        // The setter for is_active is problematic because is_active 1 means 0 and 0 means 1.
        // Also, is_active maps to active, and active is intuitive (1 means 1, 0 means 0)
        // So manually setting each one in a new public setter otherwise the campiagn won't be visible.
        //
        $newCampaign->setActiveStatus(1);
        $newCampaign->save();
        $this->newCampaignId = $newCampaign->id;

        return $newCampaign;
    }

    /**
     * Copy offers from the original campaign to the campaign copy.
     */
    private function copyOffers(): void
    {
        if ($offers = $this->original->offers) {
            if ($offers->isNotEmpty()) {
                $this->copy
                    ->offers()
                    ->attach($offers);
            }
        }
    }

    /**
     * Copy shipping profiles from the original campaign to the campaign copy.
     */
    private function copyShipping(): void
    {
        if ($shippingProfiles = $this->original->shipping_profiles) {
            if ($shippingProfiles->isNotEmpty()) {
                $this->copy
                    ->shipping_profiles()
                    ->attach($shippingProfiles);
            }
        }
    }

    /**
     * Copy payment methods from the original campaign to the campaign copy.
     */
    private function copyPaymentMethods(): void
    {
        if ($paymentMethods = $this->original->payment_methods) {
            if ($paymentMethods->isNotEmpty()) {
                // Payment method are not straight forward, first we need to get the schema_id
                // from the model, this pulls it from campaign_schema.
                //
                $copySchemaId = $this->copy->payment_method_schema_id;

                // Now create the payment methods through campaign_field_x_options model extension for the copy.
                //
                foreach ($paymentMethods as $paymentMethod) {
                    PaymentMethod::create([
                        'value'           => $paymentMethod->option_value,
                        'schema_field_id' => $copySchemaId,
                    ]);
                }
            }
        }
    }

    /**
     * Copy countries from the original campaign to the campaign copy.
     * Right now it comes from the valid_countries column as a CSV, which is not ideal.
     * @todo add a relationship table for campaign countries https://sticky.atlassian.net/browse/DEV-1187
     */
    private function copyCountries(): void
    {}

    /**
     * Copy one to many providers from the original campaign to the campaign copy.
     */
    private function copyProviders(): void
    {
        // Check for alternative payments
        //
        if ($altPayments = $this->original->alternative_payments) {
            if ($altPayments->isNotEmpty()) {
                // Copy the 1-to-many Alt pay provider types to the new campaign.
                //
                foreach ($altPayments as $altPay) {
                    AlternativePayment::create([
                        'c_id'            => $this->newCampaignId,
                        'alt_provider_id' => $altPay->alt_provider_id,
                        'name'            => $altPay->name,
                    ]);
                }

                $this->copy->update(['alt_provider_id' => 1]);
            }
        }

        // Copy the 1-to-many campaign to provider relationships.
        //
        $campaignProviders = CampaignProvider::where([
            ['campaign_id', $this->originalCampaignId]
        ])->get();

        if ($campaignProviders && $campaignProviders->isNotEmpty()) {
            foreach ($campaignProviders as $relation) {
                CampaignProvider::create([
                    'campaign_id'        => $this->newCampaignId,
                    'profile_id'         => $relation->profile_id,
                    'profile_generic_id' => $relation->profile_generic_id,
                    'account_id'         => $relation->account_id,
                    'type_id'            => $relation->type_id,
                ]);
            }
        }
    }

    /**
     * If the original campaign is a payment router, copy the relationships associated.
     */
    private function copyPaymentRouterConfigurations(): void
    {
        if ($this->original->is_load_balanced && $this->original->lbc_id) {
            if ($paymentRouterGatewayConfigurations = $this->original->paymentRouterGatewayCampaigns) {
                foreach ($paymentRouterGatewayConfigurations as $configuration) {
                    $this->copy->paymentRouterGatewayCampaigns()->create([
                        'lbc_id'            => $configuration->lbc_id,
                        'gateway_id'        => $configuration->gateway_id,
                        'campaign_id'       => $this->newCampaignId,
                        'all_payment_types' => $configuration->all_payment_types,
                        'all_products'      => $configuration->all_products,
                        'no_chargeback'     => $configuration->no_chargeback,
                        'preserve_gateway'  => $configuration->preserve_gateway,
                        'charges_today'     => $configuration->charges_today,
                        'charges_month'     => $configuration->charges_month,
                        'pre_auth_amount'   => $configuration->pre_auth_amount,
                        'default_gateway'   => $configuration->default_gateway,
                        'active'            => 1,
                        'deleted'           => 0,
                    ]);
                }

                if ($paymentRouterRoutingAttributes = $this->original->paymentRouterRoutingAttributes) {
                    foreach ($paymentRouterRoutingAttributes as $attribute) {
                        $this->copy->paymentRouterRoutingAttributes()->create([
                            'lbc_id'            => $attribute->lbc_id,
                            'gateway_id'        => $attribute->gateway_id,
                            'campaign_id'       => $this->newCampaignId,
                            'route_action_flag' => $attribute->route_action_flag,
                            'attr_entity_id'    => $attribute->attr_entity_id,
                            'active'            => 1,
                            'deleted'           => 0,
                            'attribute_entity'  => $attribute->attribute_entity,
                            'attribute_key'     => $attribute->attribute_key,
                            'attribute_value'   => $attribute->attribute_value,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Copy postback URL profiles from the original campaign to the campaign copy.
     */
    private function copyPostbackProfiles(): void
    {
        if ($postbackProfiles = $this->original->postback_profiles) {
            if ($postbackProfiles->isNotEmpty()) {
                $this->copy
                    ->postback_profiles()
                    ->attach($postbackProfiles);
            }
        }
    }

    /**
     * Copy BIN profiles from the original campaign to the campaign copy.
     */
    private function copyBinProfiles(): void
    {
        if ($binProfiles = $this->original->binProfiles) {
            if ($binProfiles->isNotEmpty()) {
                $this->copy
                    ->binProfiles()
                    ->attach($binProfiles, ['active' => 1]);
            }
        }
    }

    /**
     * Copy coupon profiles from the original campaign to the campaign copy.
     */
    private function copyCouponProfiles(): void
    {
        if ($couponCoupon = $this->original->coupon_profiles) {
            if ($couponCoupon->isNotEmpty()) {
                $this->copy
                    ->coupon_profiles()
                    ->attach($couponCoupon, ['active' => 1]);
            }
        }
    }

    /**
     * Copy returns profiles from the original campaign to the campaign copy.
     */
    private function copyReturnProfiles(): void
    {
        if ($returnProfiles = $this->original->return_profiles) {
            if ($returnProfiles->isNotEmpty()) {
                $this->copy
                    ->return_profiles()
                    ->attach($returnProfiles);
            }
        }
    }

    /**
     * Copy tax profiles from the original campaign to the campaign copy.
     */
    private function copySalesTaxProfiles(): void
    {
        if ($salesTaxProfiles = $this->original->salesTaxProfiles) {
            if ($salesTaxProfiles->isNotEmpty()) {
                $this->copy
                    ->salesTaxProfiles()
                    ->attach($salesTaxProfiles);
            }
        }
    }

    /**
     * LEGACY ONLY - Copy old campaign to product relationship.
     */
    private function copyLegacyCampaignProducts(): void
    {
        if ($legacyProducts = $this->original->products) {
            if ($legacyProducts->isNotEmpty()) {
                $this->copy
                    ->products()
                    ->attach($legacyProducts);

                $this->copy
                    ->update(['product_id' => $this->original->product_id]);
            }
        }
    }
}
