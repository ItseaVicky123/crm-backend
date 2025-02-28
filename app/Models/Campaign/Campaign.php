<?php

namespace App\Models\Campaign;

use App\Models\AccountUpdaterProviderProfile;
use App\Models\BaseModel;
use App\Models\BinManagement\BinProfile;
use App\Models\ChargebackProviderProfile;
use App\Models\CollectionProviderProfile;
use App\Models\DataVerificationProviderProfile;
use App\Models\EmailProviderProfile;
use App\Models\FulfillmentProviderProfile;
use App\Models\LoyaltyProviderProfile;
use App\Models\MembershipProviderProfile;
use App\Models\OrderConfirmationProviderProfile;
use App\Models\Prospect;
use App\Models\ProspectProviderProfile;
use App\Models\Tax\SalesTaxProfile;
use App\Models\TaxProviderProfile;
use App\Models\VolumeDiscounts\VolumeDiscount;
use App\Models\Warehouse;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use App\Models\Coupon as CouponProfile;
use App\Models\Offer\Offer as OfferProfile;
use App\Models\Postback as PostbackProfile;
use App\Models\Product as ProductProfile;
use App\Models\ReturnProfile as ReturnProfileProfile;
use App\Models\Shipping as ShippingProfile;
use App\Models\Campaign\Field\Option\PaymentMethod;
use App\Models\Campaign\Field\Field;
use App\Models\Channel;
use App\Models\Country;
use App\Models\FraudProviderProfile;
use App\Models\GatewayProfile;
use App\Models\Order;
use App\Models\PaymentRouter;
use App\Traits\HasImmutable;
use App\Traits\CampaignPermissions;

/**
 * Class Campaign
 * @package App\Models\Campaign
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $max_grace_period
 * @property string $customer_list_id
 * @property string $prospect_list_id
 * @property int $is_prepaid_blocked
 * @property int $is_custom_price_allowed
 * @property string $rebill_daily_amount
 * @property int $enabled_max_rebill_amount_per_day
 * @property int $is_collections_enabled
 * @property string $max_rebill_amount_per_day
 * @property int $is_archived
 * @property int $is_active
 * @property GatewayProfile|null $gateway
 * @property Channel $channel
 * @property PaymentRouter|null $payment_router
 * @property FulfillmentProviderProfile|null $fulfillment_provider
 * @property OrderConfirmationProviderProfile|null $callConfirmProvider
 * @property ChargebackProviderProfile|null $chargebackProvider
 * @property MembershipProviderProfile|null $membershipProvider
 * @property EmailProviderProfile|null $email_provider
 * @property TaxProviderProfile|null $tax_provider
 * @property DataVerificationProviderProfile|null $data_verification_provider
 * @property Collection $offers
 * @property Collection $payment_methods
 * @property Collection $countries
 * @property Collection $shipping_profiles
 * @property Collection $salesTaxProfiles
 * @property Collection $postback_profiles
 * @property Collection $return_profiles
 * @property Collection $coupon_profiles
 * @property Collection $fraud_providers
 * @property Collection $prospect_providers
 * @property Collection $collectionProviders
 * @property Collection $accountUpdaterProviders
 * @property Collection $loyalty_provider
 * @property Collection $binProfiles
 * @property Carbon $created_at
 *
 * @method static Campaign findOrFail(int $id)
 * @method static Builder|Campaign find($id, $columns = ['*'])
 * @method static Builder|Campaign whereNotIn(string $column, array $values, string $boolean = 'and')
 * @method static Builder withoutGlobalScopes(array $scopes = null)
 * @method static Builder withTrashed()
 */
class Campaign extends BaseModel
{
    use LimeSoftDeletes, HasCreator, HasImmutable, CampaignPermissions;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';
    const UPDATED_BY = 'update_id';
    const CREATED_BY = 'created_id';

    /**
     * @var string
     */
    protected $primaryKey = 'c_id';

    /**
     * @var string
     */
    protected $table = 'campaigns';

    /**
     * @var int
     */
    public $perPage = 10;

    /**
     * @var array
     */
    protected $maps = [
        // IDs
        'id'                          => 'c_id',
        'campaign_id'                 => 'c_id',
        'fulfillment_id'              => 'fulfillmentId',
        'check_provider_id'           => 'checkProviderId',
        'membership_provider_id'      => 'membershipProviderId',
        'call_confirm_provider_id'    => 'callconfirmProviderId',
        'chargeback_provider_id'      => 'chargebackProviderId',
        'prospect_provider_id'        => 'prospectProviderId',
        'email_provider_id'           => 'emailProviderId',
        'fraud_provider_id'           => 'fraudProviderId',
        'shipping_id'                 => 'c_shipping_id',
        'payment_router_id'           => 'lbc_id',
        // Flags
        'is_archived'                 => 'archived_flag',
        'is_deleted'                  => 'deleted',
        'is_prepaid_blocked'          => 'use_pre_paid',
        'is_custom_price_allowed'     => 'allow_custom_pricing',
        'is_avs_enabled'              => 'useAVS',
        'is_collections_enabled'      => 'collections_flag',
        'is_active'                   => 'active',
        // Dates
        'created_at'                  => self::CREATED_AT,
        'updated_at'                  => self::UPDATED_AT,
        'archived_at'                 => 'archive_date',
        // Misc
        'name'                        => 'c_name',
        'description'                 => 'c_desc',
        'pre_auth_amount'             => 'higherDollarPreAuth',
        'is_linktrust_postback_pixel' => 'linktrustPostBackPixelOnOff',
        'linktrust_campaign_id'       => 'linktrustCampaignId',
        // Autoresponder list IDs
        'prospect_list_id'            => 'i_contact_prospect',
        'customer_list_id'            => 'i_contact_client',
        'created_by'                  => self::CREATED_BY,
        'updated_by'                  => self::UPDATED_BY,
    ];

    /**
     * DO NOT CHANGE
     * Unable to use $guarded because of maps
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'pre_auth_amount',
        'product_id',
        // IDs
        //
        'created_by',
        'updated_by',
        'channel_id',
        'gateway_id',
        'warehouse_id',
        'alt_provider_id',
        'payment_router_id',
        'fulfillment_id',
        'check_provider_id',
        'membership_provider_id',
        'call_confirm_provider_id',
        'chargeback_provider_id',
        'prospect_provider_id',
        'email_provider_id',
        'fraud_provider_id',
        'tax_provider_id',
        'data_verification_provider_id',
        'shipping_id',
        'expense_profile_id',
        'gateway_descriptor',
        'site_url',
        'countries',
        'custom_products',
        'is_linktrust_postback_pixel',
        'linktrust_campaign_id',
        'enabled_max_rebill_amount_per_day',
        'max_rebill_amount_per_day',
        'rebill_daily_amount',
        'form_type',
        // autoresponder list id
        'prospect_list_id',
        'customer_list_id',
        // Flags
        'is_archived',
        'is_deleted',
        'is_prepaid_blocked',
        'is_custom_price_allowed',
        'is_avs_enabled',
        'is_collections_enabled',
        'is_active',
        'is_load_balanced',
        'max_grace_period',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'c_id',
        // Flags
        'is_archived',
        'is_prepaid_blocked',
        'is_custom_price_allowed',
        'is_avs_enabled',
        'is_collections_enabled',
        'is_active',
        // Dates
        //
        'created_at',
        'updated_at',
        'archived_at',
        // Misc
        //
        'name',
        'description',
        'site_url',
        'pre_auth_amount',
        'creator',
        'updator',
        'countries',
        // Relationship columns
        //
        'gateway_id',
        'fulfillment_id',
        'check_provider_id',
        'membership_provider_id',
        'call_confirm_provider_id',
        'chargeback_provider_id',
        'prospect_provider_id',
        'email_provider_id',
        'tax_provider_id',
        'data_verification_provider_id',
        // Relationships
        //
        'channel',
        'gateway',
        'payment_methods',
        'alternative_payments',
        'offers',
        'shipping_profiles',
        'return_profiles',
        'postback_profiles',
        'coupon_profiles',
        'fraud_providers',
        'prospect_list_id',
        'customer_list_id',
        'volume_discounts',
        'max_grace_period',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        // Flags
        //
        'is_archived',
        'is_prepaid_blocked',
        'is_custom_price_allowed',
        'is_avs_enabled',
        'is_collections_enabled',
        'is_active',
        // Dates
        //
        'created_at',
        'updated_at',
        'archived_at',
        // Misc
        //
        'name',
        'description',
        'pre_auth_amount',
        'creator',
        'updator',
        'countries',
        // Relationship columns
        //
        'fulfillment_id',
        'check_provider_id',
        'membership_provider_id',
        'call_confirm_provider_id',
        'chargeback_provider_id',
        'prospect_provider_id',
        'email_provider_id',
        // Relationships
        //
        'offers',
        'channel',
        'payment_methods',
        'gateway',
        'alternative_payments',
        'shipping_profiles',
        'return_profiles',
        'postback_profiles',
        'coupon_profiles',
        'products',
        'fraud_providers',
        'volume_discounts',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'archived_at',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'integration_type_id' => 2,
    ];

    /**
     * @var null|int
     */
    protected $paymentSchemaFieldId = null;

    /**
     * @todo drop
     * c_parent_id
     * i_contact_api_id
     * redirection_url
     * error_url
     * wrong_land_url
     * second_error_url
    */

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_store', function (Builder $builder) {
            $builder->whereNull('is_store');
        });

        static::campaignPermissionBoot();

        static::creating(function ($campaign) {
            $campaign->created_by = get_current_user_id();
        });

        static::created(function ($campaign) {
            Field::create([
                'label_name'           => 'Payment Method',
                'field_name'           => 'cc_type',
                'order_field_name'     => 'cc_type',
                'cc_form_field'        => 'cc_type',
                'is_credit_card_field' => 1,
                'is_required'          => 1,
                'field_order'          => 10,
                'campaign_id'          => $campaign->id,
                'field_type'           => 'dropdown',
            ]);
        });

        static::updating(function ($campaign) {
            $campaign->checkImmutable();
            $campaign->created_by = get_current_user_id();
        });

        static::deleting(function ($campaign) {
            $campaign->checkImmutable();
            $campaign->offers()
                ->detach();
            $campaign->shipping_profiles()
                ->detach();
            $campaign->destroyPaymentMethodsJunction();
            $campaign->return_profiles()
                ->detach();
            $campaign->alternative_payments()
                ->delete();
            $campaign->postback_profiles()
                ->detach();
            $campaign->coupon_profiles()
                ->detach();
        });
    }

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'campaign_order_id', 'c_id');
    }

    /**
     * @return BelongsToMany
     */
    public function offers()
    {
        return $this->belongsToMany(
            OfferProfile::class,
            'billing_campaign_offer',
            'campaign_id',
            'offer_id',
            'c_id',
            'id'
        );
    }

    /**
     * BIN profile relationships associated with this campaign.
     * @return BelongsToMany
     */
    public function binProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            BinProfile::class,
            'bin_campaign_jct',
            'campaign_id',
            'bin_profile_id',
            'c_id',
            'id'
        );
    }

    /**
     * Sales tax profile relationships associated with this campaign.
     * @return BelongsToMany
     */
    public function salesTaxProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesTaxProfile::class,
            'campaign_tax_profile',
            'campaign_id',
            'tax_profile_id',
            'c_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOffersAttribute()
    {
        return $this->offers()->get();
    }

    /**
     * @return HasOne
     */
    public function gateway()
    {
        return $this->hasOne(GatewayProfile::class, 'gateway_id', 'gateway_id');
    }

    /**
     * @return Model|HasOne|object|null
     */
    public function getGatewayAttribute()
    {
        return $this->gateway()->first();
    }

    /**
     * @return HasMany
     */
    public function alternative_payments()
    {
        return $this->hasMany(AlternativePayment::class, 'c_id', 'c_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAlternativePaymentsAttribute()
    {
        return $this->alternative_payments()->get();
    }

    /**
     * @return HasOne
     */
    public function payment_router()
    {
        return $this->hasOne(PaymentRouter::class, 'id', 'lbc_id');
    }

    /**
     * The relationships between the payment router, campaign, and gateway.
     * @return HasMany
     */
    public function paymentRouterGatewayCampaigns(): HasMany
    {
        return $this->hasMany(PaymentRouterGatewayCampaign::class, 'campaign_id', 'c_id');
    }

    /**
     * The relationships between the payment router, campaign, and gateway mapped to routing configurations.
     * @return HasMany
     */
    public function paymentRouterRoutingAttributes(): HasMany
    {
        return $this->hasMany(PaymentRouterRoutingAttribute::class, 'campaign_id', 'c_id');
    }

    /**
     * Volume discount relationships associated with this campaign.
     * @return BelongsToMany
     */
    public function volume_discounts(): BelongsToMany
    {
        return $this->belongsToMany(
            VolumeDiscount::class,
            'volume_discount_campaigns',
            'campaign_id',
            'volume_discount_id',
            'c_id',
            'id'
        );
    }

    /**
     * Get volume discounts attached to the campaign.
     * @return Collection
     */
    public function getVolumeDiscountsAttribute(): Collection
    {
        return $this->volume_discounts()->get();
    }

    /**
     * @return Model|HasOne|object|null
     */
    public function getPaymentRouterAttribute()
    {
        return $this->payment_router()->first();
    }

    /**
     * Payment method junction is ghetto...
     * You were warned
     */

    /**
     * @return int
     */
    public function getPaymentMethodSchemaIdAttribute()
    {
        if (! isset($this->paymentSchemaFieldId)) {
            $this->paymentSchemaFieldId = 0;
            $field                      = Field::where('campaign_id', $this->getAttribute('id'))
                ->where('field_name', 'cc_type')
                ->first();

            if ($field) {
                $this->paymentSchemaFieldId = $field->id;
            }
        }

        return $this->paymentSchemaFieldId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentMethodsAttribute()
    {
        return PaymentMethod::where('schema_field_id', $this->getPaymentMethodSchemaIdAttribute())
            ->get();
    }

    /**
     * @param array $methods
     * @return \Illuminate\Support\Collection
     */
    public function createPaymentMethodsJunction(array $methods = [])
    {
        $created = [];

        if ($schemaId = $this->getPaymentMethodSchemaIdAttribute()) {
            foreach ($methods as $method) {
                $created[] = PaymentMethod::create([
                    'value'           => $method,
                    'schema_field_id' => $schemaId,
                ]);
            }
        }

        return collect($created);
    }

    /**
     * @return bool
     */
    public function destroyPaymentMethodsJunction()
    {
        $this->getPaymentMethodsAttribute()
            ->each(function ($method) {
                $method->delete();
            });

        return true;
    }

    /**
     * @param $countryIds
     */
    public function setCountriesAttribute($countryIds)
    {
        $this->attributes['valid_countries'] = implode(',', (array) $countryIds);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCountriesAttribute()
    {
        return Country::whereIn(
            'id',
            explode(',', $this->attributes['valid_countries'])
        )->get();
    }

    /**
     * @return BelongsToMany
     */
    public function shipping_profiles()
    {
        return $this->belongsToMany(
            ShippingProfile::class,
            'campaign_shipping',
            'campaign_id',
            'shipping_id',
            'c_id',
            's_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getShippingProfilesAttribute()
    {
        return $this->shipping_profiles()->get();
    }

    /**
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(
            ProductProfile::class,
            'campaign_products',
            'campaign_id',
            'product_id',
            'c_id',
            'products_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsAttribute()
    {
        return $this->products()->get();
    }

    /**
     * @return HasOne
     */
    public function channel()
    {
        return $this->hasOne(Channel::class, 'id', 'channel_id');
    }

    /**
     * @param int $isActive
     */
    public function setActiveStatus(int $isActive)
    {
        $isActiveValue                 = $isActive ? 0 : 1;
        $this->attributes['active']    = $isActive;
        $this->attributes['is_active'] = $isActiveValue;
    }
    /**
     * @return Model|HasOne|object|null
     */
    protected function getChannelAttribute()
    {
        return $this->channel()->first();
    }

   /**
    * @param $value
    */
    protected function setIsActiveAttribute($value)
    {
        $this->attributes['active']    = $value;
        $this->attributes['is_active'] = !$value;
    }

    /**
     * @return BelongsToMany
     */
    public function return_profiles(): BelongsToMany
    {
        return $this->belongsToMany(
            ReturnProfileProfile::class,
            'returns_campaign_jct',
            'campaign_id',
            'profile_id',
            'c_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReturnProfilesAttribute()
    {
        return $this->return_profiles()->get();
    }

    /**
     * @return BelongsToMany
     */
    public function postback_profiles(): BelongsToMany
    {
        return $this->belongsToMany(
            PostbackProfile::class,
            'campaign_postbacks',
            'campaign_id',
            'postback_id',
            'c_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPostbackProfilesAttribute()
    {
        return $this->postback_profiles()->get();
    }

    /**
     * @return BelongsToMany
     */
    public function coupon_profiles()
    {
        return $this->belongsToMany(
            CouponProfile::class,
            'coupon_campaign_jct',
            'campaign_id',
            'coupon_id',
            'c_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCouponProfilesAttribute()
    {
        return $this->coupon_profiles()->wherePivot('active', 1)->get();
    }

    /**
     * @return HasMany
     */
    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class, 'campaign_id', 'c_id');
    }

    /**
     * @return HasMany
     */
    public function fraud_providers(): HasMany
    {
        return $this->providers()
            ->where('provider_type_id', FraudProviderProfile::PROVIDER_TYPE);
    }

    /**
     * @return HasMany
     */
    public function loyalty_provider(): HasMany
    {
        return $this->providers()->where('provider_type_id', LoyaltyProviderProfile::PROVIDER_TYPE);
    }

    /**
     * @return Model|HasMany|object|null
     */
    protected function getLoyaltyProviderAttribute()
    {
       return $this->loyalty_provider()->first();
    }

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
    */
    public function fraud_provider_profiles()
    {
        return $this->hasManyThrough(FraudProviderProfile::class, Provider::class, 'campaign_id', 'fraudProviderId', 'c_id', 'profile_id')
            ->withoutGlobalScope(ActiveScope::class)
            ->where('provider_type_id', FraudProviderProfile::PROVIDER_TYPE);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getFraudProvidersAttribute()
    {
        return $this->fraud_providers()
            ->get()
            ->pluck('profile_id');
    }

    /**
     * @return HasOne
     */
    public function email_provider(): HasOne
    {
        return $this->hasOne(EmailProviderProfile::class, 'emailProviderId', 'emailProviderId');
    }

    /**
     * @return HasOne
     */
    public function fulfillment_provider(): HasOne
    {
        return $this->hasOne(FulfillmentProviderProfile::class, 'fulfillmentId', 'fulfillmentId');
    }

    /**
     * @return HasOne
     *
     * @deprecated this is wrong, the relationship should be hasManyThrough use salesTaxProfiles instead
     */
    public function tax_provider(): HasOne
    {
        return $this->hasOne(TaxProviderProfile::class, 'tax_provider_id', 'tax_provider_id');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeForPostbacksApi(Builder $query)
    {
        return $query->select('c_id', 'c_name')
            ->where('deleted', 0)
            ->where('archived_flag', 0)
            ->where('active', 1)
            ->orderBy('c_id', 'DESC');
    }

    /**
     * @return bool
     */
    public function canActivate()
    {
        return (
            ($this->gateway()->exists() || $this->alternative_payments()->exists() || $this->is_load_balanced) &&
            ($this->payment_methods->count() || $this->paymentRouterGatewayCampaigns()->count()) &&
            $this->offers()->exists() &&
            $this->countries->count() &&
            $this->shipping_profiles()->exists()
        );
    }

    /**
     * @return HasMany
     */
    public function prospect_providers(): HasMany
    {
        return $this->providers()
            ->where('provider_type_id', ProspectProviderProfile::PROVIDER_TYPE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function prospect_provider_profiles()
    {
        return $this->hasManyThrough(ProspectProviderProfile::class, Provider::class, 'campaign_id', 'prospectProviderId', 'c_id', 'profile_id')
            ->withoutGlobalScope(ActiveScope::class)
            ->where('provider_type_id', ProspectProviderProfile::PROVIDER_TYPE);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getProspectProvidersAttribute()
    {
        return $this->prospect_providers()
            ->get()
            ->pluck('profile_id');
    }

    /**
     * @return HasMany
     */
    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'campaign_id', 'c_id');
    }

    /**
     * @return HasOne
     */
    public function data_verification_provider(): HasOne
    {
        return $this->hasOne(DataVerificationProviderProfile::class, 'id', 'data_verification_provider_id');
    }

    /**
     * @return HasOne
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class);
    }

    /**
     * @return Model|object|null
     */
    public function getWarehouseAttribute()
    {
        return $this->warehouse()->first();
    }

    public function callConfirmProvider(): HasOne
    {
        return $this->hasOne(OrderConfirmationProviderProfile::class, 'callconfirmProviderId', 'callconfirmProviderId');
    }

    public function chargebackProvider(): HasOne
    {
        return $this->hasOne(ChargebackProviderProfile::class, 'chargebackProviderId', 'chargebackProviderId');
    }

    public function membershipProvider(): HasOne
    {
        return $this->hasOne(MembershipProviderProfile::class, 'membershipProviderId', 'membershipProviderId');
    }

    public function collectionProviders(): HasMany
    {
        return $this->providers()->where('provider_type_id', CollectionProviderProfile::PROVIDER_TYPE);
    }

    public function accountUpdaterProviders(): HasMany
    {
        return $this->providers()->where('provider_type_id', AccountUpdaterProviderProfile::PROVIDER_TYPE);
    }
}
