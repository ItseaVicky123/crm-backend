<?php

namespace App\Models;

use App\Exceptions\Orders\ConsentAlreadyAppliedException;
use App\Exceptions\Orders\ConsentNotRequiredException;
use App\Exceptions\Orders\ConsentWithoutNotificationException;
use App\Exceptions\Orders\VoidInvalidProviderException;
use App\Exceptions\Orders\VoidInvalidStateException;
use App\Exceptions\Orders\VoidProhibitedByProviderException;
use App\Exceptions\Orders\VoidZeroException;
use App\Exceptions\ProviderActionNotAllowed;
use App\Facades\SMC;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\BillingModel\OrderSubscription;
use App\Models\Campaign\Campaign;
use App\Models\Contact\Contact;
use App\Models\Credits\Subscription as SubscriptionCredit;
use App\Models\NotificationEventTypes\Order\ConsentRequested;
use App\Models\Order\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderAttributes\Announcement;
use App\Models\OrderAttributes\AwaitingRetryDate;
use App\Models\OrderAttributes\Backorder;
use App\Models\OrderAttributes\BadShippingAddress;
use App\Models\OrderAttributes\BuyXGetYCouponId;
use App\Models\OrderAttributes\ConsentRequired;
use App\Models\OrderAttributes\ConsentWorkflowType;
use App\Models\OrderAttributes\DelayConfirmationNotification;
use App\Models\OrderAttributes\FulfillmentNumber;
use App\Models\OrderAttributes\LineItemSequence;
use App\Models\OrderAttributes\OriginTypeId;
use App\Models\OrderAttributes\ReshipmentCount;
use App\Models\OrderAttributes\SkipFulfillmentPost;
use App\Models\OrderAttributes\SplitShipment;
use App\Models\OrderLineItems\LineItemCustomOption;
use App\Models\OrderAttributes\TaxTransactionNumber;
use App\Models\OrderLineItems\OrderProductVolumeDiscountPrice;
use App\Models\Payment\ContactPaymentSource;
use App\Models\Payment\PaymentType;
use App\Models\Payment\Transaction\NMIPaysafe;
use App\Models\SmartDunning\SmartDunningRetryDate;
use App\Models\SmartDunning\SmartDunningRetryTimeOfDay;
use App\Models\TrialWorkflow\TrialWorkflowLineItem;
use App\Scopes\ActiveScope;
use App\Scopes\OrderGlobalScope;
use App\Traits\CampaignPermissions;
use App\Traits\CustomFieldEntity;
use App\Traits\HasSubscriptionPieces;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use stdClass;
use Illuminate\Support\Arr;

/**
 * Class Order
 *
 * @package App\Models
 *
 * @method static Builder|self find( string|int $orderId)
 * @method static withoutGlobalScopes()
 * @method static Builder|Order where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Order whereIn(string $column, array $values, string $boolean = 'and', bool $not = false)
 *
 * @property-read ConsentWorkflowType $consent_workflow_type
 * @property-read Order               $common_ancestor
 * @property      Customer            $customer
 * @property-read string              $consent_id                          ID used to track rebill consent status for subscriptions
 * @property-read bool                $uses_internal_consent               True if we handle the consent workflow for this order
 * @property-read bool                $ancestor_uses_internal_consent      True if we handle the consent workflow for this order's ancestor
 * @property-read bool                $uses_paysafe_consent                True if paysafe handles the consent workflow for this order
 * @property-read bool                $ancestor_uses_paysafe_consent       True if paysafe handles the consent workflow for this order's ancestor
 * @property-read bool                $is_paysafe_gateway                  True if order used a paysafe gateway
 * @property-read bool                $is_snapshot_required_cc             True if order used a cc type that requires snapshots of the html sent for all notifications
 * @property      bool                $is_recurring                        True if order is recurring
 * @property-read bool                $should_store_notification_snapshots True if we should store snapshots of the html sent for all notifications
 * @property-read Collection          $all_items                           @deprecated use $all_order_items, see https://sticky.atlassian.net/browse/DEV-1358
 * @property-read Collection          $all_order_items                     Collection of Subscription models including the
 *                                                                         main order and all of it's upsells
 * @property-read Collection          $all_recurring_items                 Collection of Subscription models including the
 *                                                                         recurring main order and all of it's recurring upsells
 * @property-read bool                $has_recurring_items                 true if all_recurring_items contains any records
 *
 * @property-read int                 $id                                  Order ID
 * @property-read OrderLineItem       $line_items                          Order line items
 * @property-read Order               $parent                              Parent order
 *
 * @property-read Collection          $active_recurring_items              Collection of All Active Recurring Items including Upsells
 * @property      mixed               $amount_refunded                     Amount refunded on the order
 * @property      stdClass            $billing                             Billing information
 * @property      mixed               $billing_country                     Billing Country
 * @property      Campaign            $campaign                            The Campaign used for the order
 * @property      int                 $campaign_id                         The ID of the campaign used for the order
 * @property      string              $cc_token                            The token used to store the credit card details
 * @property      string              $cc_last_four                        The last four digits of the credit card
 * @property      string              $cc_expiry                           The expiry date of the credit card
 * @property      string              $cc_type                             The type of the credit card
 * @property      string              $cc_encrypted                        The encrypted credit card details
 * @property      string              $email                               The email address of the customer
 * @property      mixed               $gateway_id                          The gateway ID of payment provider used for order
 * @property      string              $ip_address                          The IP address of the customer
 * @property      bool                $is_chargeback                       Has order been marked chargeback
 * @property      mixed               $is_fraud                            Order marked fraud
 * @property      mixed               $is_fulfillment_posted               Has order been posted to fulfillment
 * @property      mixed               $is_shipped                          Has order been shipped - has tracking number
 * @property      string              $phone                               The phone number of the customer
 * @property      mixed               $rebill_depth                        The rebill depth within the subscription
 * @property      mixed               $refund_type_id                      The type of refund - full or partial or void
 * @property      mixed               $retry_attempt_no                    Retry Attempt Number (if in Decline Salvage) - column: int_2
 * @property      stdClass            $shipping                            Shipping information
 * @property      mixed               $status_id                           The Order Status ID
 * @property      string              $subscription_id                     The subscription ID of the order
 * @property      mixed               $transaction_id                      Payment Provider Transaction ID
 * @property      mixed               $is_active_subscription              Boolean for whether order is active subscription
 * @property Collection $products
 * @property string $customers_email_address
 * @property float $order_total
 * @property int $campaign_order_id
 * @property string $stopRecurringOnNextSuccess
 */
class Order extends Subscription
{
    use LimeSoftDeletes, CustomFieldEntity, HasSubscriptionPieces, CampaignPermissions;

    public const ACTIVE_FLAG        = false;
    public const CREATED_AT         = 't_stamp';
    public const UPDATED_AT         = 'date_0';
    public const ENTITY_ID          = 2;
    public const PARTIALLY_REFUNDED = 1;
    public const FULLY_REFUNDED     = 2;
    public const VOIDED             = 3;
    public const REFUND_REVERSED    = 4;
    public const IS_RMA_RETURNED    = 2;

    public const SNAPSHOT_REQUIRED_CC_TYPES = [
        'master',
        'visa',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'orders_id';

    /**
     * @var array
     */
    protected $guarded = [
        'orders_id',
    ];

    /**
     * @var string
     */
    protected $morphClass = '1';

    /** @var Collection */
    protected $allItems;

    /**
     * @var array
     */
    protected $dates = [
        't_stamp',
        'date_purchased',
        'hold_date',
        'last_modified',
        'orders_date_finished',
        'recurring_date',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'campaign_id',
        'campaign_name',
        'customer_id',
        'promo_code_id',
        // Flags
        'is_reprocessed',
        'is_salvaged',
        'is_fraud',
        'is_chargeback',
        // Dates
        'created_at',
        'updated_at',
        'recur_at',
        // Customer
        'email',
        'phone',
        'shipping',
        'billing',
        // Misc
        'status',
        'payment',
        'fulfillment',
        'subscription',
        'confirmation',
        'currency',
        'custom_fields',
        'products',
        'history_notes',
        'line_items',
        'order_customer_types',
        'mc_currency',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'status',
        'campaign_id',
        'campaign_name',
        'customer_id',
        // Customer
        'email',
        'phone',
        'shipping',
        'billing',
        // IDs
        'promo_code_id',
        // Flags
        'is_reprocessed',
        'is_salvaged',
        'is_fraud',
        'is_chargeback',
        // Dates
        'created_at',
        'updated_at',
        'recur_at',
        // Misc
        'status',
        'payment',
        'fulfillment',
        'subscription',
        'confirmation',
        'currency',
        'custom_fields',
        'products',
        'history_notes',
        'line_items',
        'is_rebill',
        'is_backorder',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'                     => 'orders_id',
        // IDs
        'ancestor_id'            => 'commonAncestorOrderId',
        'parent_id'              => 'parent_order_id',
        'campaign_id'            => 'campaign_order_id',
        'campaign_name'          => 'campaign.name',
        'campaign_description'   => 'campaign.description',
        'customer_id'            => 'customers_id',
        'prospect_id'            => 'prospects_id',
        'gateway_id'             => 'gatewayId',
        'confirmation_id'        => 'orderConfirmationId',
        'alt_pay_payer_id'       => 'text_0',
        'custom_variant_id'      => 'text_3',
        'promo_code_id'          => 'int_3',
        'rma_reason_id'          => 'RMAReasonCodeId',
        'return_reason_id'       => 'paypal_ipn_id',
        'click_id'               => '3DAuthToken',
        'unbundled_child_id'     => 'child_order_id',
        'custom_rec_prod_id'     => 'recurring_product_custom',
        'refund_type_id'         => 'refundType',
        'return_type_id'         => 'isRMA',
        // Flags
        'is_deleted'             => 'deleted',
        'is_finished'            => 'isChargebackReversal',
        'is_shippable'           => 'payment_module_code',
        'is_shipped'             => 'shipping_module_code',
        'is_reprocessed'         => 'wasReprocessed',
        'is_salvaged'            => 'wasSalvaged',
        'is_fraud'               => 'isFraud',
        'is_chargeback'          => 'isChargeback',
        'is_confirmed'           => 'orderConfirmed',
        'is_tracking_posted'     => 'hasTrackingBeenPosted',
        'tracking_number'        => 'tracking_num',
        'is_fulfillment_posted'  => 'hasBeenPosted',
        'is_stop_next_recur'     => 'stopRecurringOnNextSuccess',
        'is_hold_from_recurring' => 'customers_address_format_id',
        'is_preserve_gateway'    => 'gatewayPreserve',
        'is_checking_limbo'      => 'billing_address_format_id',
        'is_test'                => 'is_test_cc',
        // Dates
        'created_at'             => 't_stamp',
        'hold_at'                => 'hold_date',
        'recur_at'               => 'recurring_date',
        'retry_at'               => 'date_purchased',
        'shipped_at'             => 'orders_date_finished',
        'updated_at'             => 'date_0',
        'confirmed_at'           => 'orderConfirmedDateTime',
        'returned_at'            => 'last_modified',
        // Customer
        'email'                  => 'customers_email_address',
        'phone'                  => 'customers_telephone',
        'first_name'             => 'delivery_fname',
        'last_name'              => 'delivery_lname',
        'address'                => 'delivery_street_address',
        'address2'               => 'delivery_suburb',
        'city'                   => 'delivery_city',
        'state'                  => 'delivery_state',
        'state_id'               => 'delivery_state_id',
        'zip'                    => 'delivery_postcode',
        'country_id'             => 'delivery_country',
        'ship_first_name'        => 'delivery_fname',
        'ship_last_name'         => 'delivery_lname',
        'ship_address'           => 'delivery_street_address',
        'ship_address2'          => 'delivery_suburb',
        'ship_city'              => 'delivery_city',
        'ship_state'             => 'delivery_state',
        'ship_state_id'          => 'delivery_state_id',
        'ship_zip'               => 'delivery_postcode',
        'ship_country_id'        => 'delivery_country',
        'shipping_country_name'  => 'ship_country.name',
        'shipping_country_iso2'  => 'ship_country.iso_2',
        'shipping_country_iso3'  => 'ship_country.iso_3',
        'bill_first_name'        => 'billing_fname',
        'bill_last_name'         => 'billing_lname',
        'bill_address'           => 'billing_street_address',
        'bill_address2'          => 'billing_suburb',
        'bill_city'              => 'billing_city',
        'bill_zip'               => 'billing_postcode',
        'bill_state'             => 'billing_state',
        'bill_state_id'          => 'billing_state_id',
        'bill_country_id'        => 'billing_country',
        'billing_country_name'   => 'bill_country.name',
        'billing_country_iso2'   => 'bill_country.iso_2',
        'billing_country_iso3'   => 'bill_country.iso_3',
        // Misc
        'status_id'              => 'orders_status',
        'payment_method'         => 'cc_type',
        'cc_encrypted'           => 'charge_c',
        'cc_first_6'             => 'charge_c_ins',
        'cc_last_4'              => 'charge_c_mod',
        'cc_length'              => 'charge_c_length',
        'cvv_length'             => 'charge_sc_length',
        'cc_expiry'              => 'cc_expires',
        'orig_cc_encrypted'      => 'charge_c_orig',
        'orig_cc_first_6'        => 'charge_c_orig_ins',
        'orig_cc_last_4'         => 'charge_c_orig_mod',
        'rebill_discount'        => 'rebillDiscount',
        'retry_discount_pct'     => 'int_1',
        'retry_discount_amt'     => 'amount_1',
        'retry_attempt_no'       => 'int_2',
        'rma_number'             => 'RMANumber',
        'fulfillment_number'     => 'fulfillmentNumber',
        'confirmation_status'    => 'orderConfirmedStatus',
        'custom_subscription'    => 'text_2',
        'rebill_depth'           => 'rebillDepth',
        'forecasted_revenue'     => 'currency_value',
        'amount_refunded'        => 'amountRefundedSoFar',
        'alt_pay_token'          => 'text_1',
        'cc_token'               => 'text_1',
        'currency'               => 'gateway.currency',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'rebillDepth'                 => 0,
        'customers_address_format_id' => 0,
        'billing_address_format_id'   => 0,
        'delivery_address_format_id'  => 0,
        'hold_date'                   => self::EMPTY_DATE,
        'date_purchased'              => self::EMPTY_DATE,
        'last_modified'               => self::EMPTY_DATE,
        'notes'                       => '',
        'deleted'                     => 0,
        'products'                    => null,
        'billing_country'             => 0,
        'delivery_country'            => 0,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rebillDepth' => 'integer',
    ];

    /**
     * @var string[]
     */
    public $token_map = [
        'afid'                             => 'AFID',
        'affid'                            => 'AFFID',
        'affiliate'                        => 'affiliate_token',
        'aid'                              => 'AID',
        'billing_address_2'                => 'bill_address2',
        'billing_address_1'                => 'bill_address',
        'c1'                               => 'C1',
        'c2'                               => 'C2',
        'c3'                               => 'C3',
        'sid'                              => 'SID',
        'currency'                         => 'currency_code',
        'decline_salvage_discount_percent' => 'retry_discount_pct',
        'digital_delivery_password'        => 'digital_delivery_password',//TODO
        'digital_delivery_username'        => 'digital_delivery_username',//TODO
        'customer_email'                   => 'email',
        'shipping_first_name'              => 'ship_first_name',
        'shipping_last_name'               => 'ship_last_name',
        'shipping_address_1'               => 'ship_address',
        'shipping_address_2'               => 'ship_address2',
        'shipping_city'                    => 'ship_city',
        'shipping_state'                   => 'ship_state',
        'shipping_state_id'                => 'ship_state_id',
        'shipping_zip'                     => 'ship_zip',
        'shipping_country_iso_3'           => 'shipping_country_iso3',
        'shipping_flag'                    => 'is_shippable',
        'non_taxable_amount'               => 'non_taxable_total',
        'opt'                              => 'OPT',
        'order_id'                         => 'id',
        'customer_phone'                   => 'phone',
        'post_back_action'                 => 'post_back_action',//TODO
        'rebill_discount_percent'          => 'rebill_discount',
        'current_retry_count'              => 'retry_attempt_no',
        'sales_tax_percent'                => 'tax_percent',
        'amount_refunded_to_date'          => 'amount_refunded',
        'was_reprocessed'                  => 'is_reprocess',
    ];

    /**
     * @var int
     */
    public $entity_type_id = self::ENTITY_ID;

    /**
     * @var ?int
     */
    protected ?int $swappedMainToUpsellId = null;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new OrderGlobalScope);

        static::applyCampaignPermissionsBoot();

        static::creating(function ($order) {
            if (! $order->customer_id) {
                if ($customer = Customer::where('email', $order->getAttribute('email'))->first()) {
                    $order->setAttribute('customer_id', $customer->id);
                } else {
                    $customer = Customer::create([
                        'email'      => $order->getAttribute('email'),
                        'phone'      => $order->getAttribute('phone'),
                        'first_name' => $order->getAttribute('first_name'),
                        'last_name'  => $order->getAttribute('last_name'),
                    ]);

                    $order->setAttribute('customer_id', $customer->id);
                }
            }

            $legacy_fields = [
                'customers_fname'          => 'first_name',
                'customers_lname'          => 'last_name',
                'customers_street_address' => 'address',
                'customers_suburb'         => 'address2',
                'customers_city'           => 'city',
                'customers_postcode'       => 'zip',
                'customers_state'          => 'state',
                'customers_country'        => 'country_id',
            ];

            foreach ($legacy_fields as $prop => $map_to) {
                if (! strlen($order->$prop)) {
                    $order->setAttribute($prop, $order->getAttribute($map_to));
                }
            }

            if ($cc_encrypted = $order->getAttribute('charge_c')) {
                $order->setAttribute('orig_cc_encrypted', $cc_encrypted);
                $order->setAttribute('orig_cc_first_6', $order->getAttribute('cc_first_6'));
                $order->setAttribute('orig_cc_last_4', $order->getAttribute('cc_last_4'));
            }
        });

        static::updating(function ($order) {
            if (!$order->getAttribute('customers_fname') && !$order->getAttribute('customers_lname')) {
                $legacy_fields = [
                    'customers_fname'          => 'first_name',
                    'customers_lname'          => 'last_name',
                    'customers_street_address' => 'address',
                    'customers_suburb'         => 'address2',
                    'customers_city'           => 'city',
                    'customers_postcode'       => 'zip',
                    'customers_state'          => 'state',
                    'customers_country'        => 'country_id',
                ];

                foreach ($legacy_fields as $prop => $map_to) {
                    $order->setAttribute($prop, $order->getAttribute($map_to));
                }
            }
        });

        static::saving(function ($order) {
            // This is a pseudo attribute, don't include as part of updating
            unset($order->attributes['products']);
        });

        static::created(function (self $order) {
            $order
                ->order_customer_types()
                ->firstOrCreate([
                    'type'        => OrderCustomerType::PRIMARY,
                    'customer_id' => $order->customer_id,
                ]);
        });
    }

    public function getOrderCurrencyCodeAttribute() {
       return (! empty($this->attributes['currency']) && SMC::check('GATEWAY_CURRENCY_OVERRIDE') ? $this->attributes['currency'] : $this->currency->code);
    }

    /**
     * Determine if the order was rebilled on a different gateway.
     *
     * @return bool.
     */
    public function isRebilledOnDifferentGateway(): bool
    {
        if ($this->rebill_depth > 0) {
            if ($this->parent && $this->parent->gateway_id !== $this->gateway_id) {
                return true;
            }

            if ($this->common_ancestor && $this->common_ancestor->gateway_id !== $this->gateway_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return HasOne
     */
    public function campaign()
    {
        return $this->hasOne(Campaign::class, 'c_id', 'campaign_order_id');
    }

    /**
     * @return HasOne
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'customers_id', 'customers_id');
    }

    /**
     * @return HasOne
     */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'customers_email_address');
    }

    /**
     * @return HasOne
     */
    public function fulfillment_number()
    {
        return $this->hasOne(FulfillmentNumber::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function splitShipment(): HasOne
    {
        return $this->hasOne(SplitShipment::class, 'order_id', 'orders_id');
    }

    /**
     * @return Bool
     */
    public function getIsSplitShipmentAttribute(): Bool
    {
        return $this->splitShipment()->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getContactAttribute()
    {
        return Contact::firstOrCreate(
                [
                    'email' => substr($this->email, 0, 255),
                ],
                [
                    'phone'      => $this->phone,
                    'first_name' => $this->first_name,
                    'last_name'  => $this->last_name,
                ]
            );
    }

    /**
     * @return HasOne
     */
    public function order_product()
    {
        return $this->hasOne(OrderProduct::class, 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function delayConfirmationNotification(): HasOne
    {
        return $this->hasOne(DelayConfirmationNotification::class, 'order_id', 'orders_id');
    }

    /**
     * @return bool
     */
    public function getIsDelayConfirmationNotificationAttribute(): Bool
    {
        return $this->delayConfirmationNotification()->exists();
    }

    /**
     * @return int
     */
    public function getTypeIdAttribute()
    {
        return OrderSubscription::TYPE_MAIN;
    }

    /**
     * @return HasOne
     */
    public function order_subscription(): HasOne
    {
        return $this->hasOne(OrderSubscription::class, 'order_id', 'orders_id')
            ->where('type_id', OrderSubscription::TYPE_MAIN);
    }

    /**
     * @return HasOne
     */
    public function subscription_override()
    {
        return $this->hasOne(SubscriptionOverride::class, 'subscription_id', 'subscription_id')
            ->whereNull('consumed_at');
    }

    /**
     * @return mixed
     */
    public function getCcTokenAttribute()
    {
        return (string) $this->text_1;
    }

   /**
    * @return HasOne
    */
   public function payment_source()
   {
      return $this->hasOne(ContactPaymentSource::class, 'account_number', 'charge_c');
   }

    /**
     * @return bool
     */
    public function getIsInitialOrderAttribute()
    {
        return (bool) ($this->rebill_depth == 0);
    }

    /**
     * @return array
     */
    public function getNextShippingAddressAttribute()
    {
        if ($this->subscription_override && $this->subscription_override->address) {
            return $this->subscription_override->address->toArray();
        }

        return (array) $this->shipping;
    }

    /**
     * @return array
     */
    public function getNextBillingAddressAttribute()
    {
        if ($this->subscription_override && $this->subscription_override->contact_payment_source) {
            return $this->subscription_override->contact_payment_source->address->toArray();
        }

        return (array) $this->billing;
    }

    /**
     * Formerly known as "upsells"
     *
     * @return HasMany
     */
    public function additional_products(): HasMany
    {
        return $this->hasMany(Upsell::class, 'main_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rma_reason()
    {
        return $this->belongsTo(RmaReason::class, 'RMAReasonCodeId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gateway_provider()
    {
        return $this->belongsTo(GatewayProfile::class, 'gatewayId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function checking_provider()
    {
        return $this->belongsTo(CheckingProfile::class, 'gatewayId');
    }

    /**
     * @return HasOne
     */
    public function bill_country()
    {
        return $this->hasOne(Country::class, 'countries_id', 'billing_country');
    }

    /**
     * @return HasOne
     */
    public function ship_method()
    {
        return $this->hasOne(Shipping::class, 's_identity', 'shipping_method');
    }

    /**
     * @return HasOne
     */
    public function declined_cc(): HasOne
    {
        return $this->hasOne(DeclinedCC::class, 'orders_id', 'orders_id')
            ->where('is_order_or_upsell', 0);
    }

    /**
     * @return HasOne
     */
    public function decline_event(): HasOne
    {
        return $this->hasOne(DeclineEvent::class, 'orders_id', 'order_id');
    }

    /**
     * @return int
     */
    public function getRetryAttemptAttribute()
    {
        return $this->declined_cc ? $this->declined_cc->attempt_no : 0;
    }

    /**
     * @return HasMany
     */
    public function documents()
    {
        return $this->hasMany(OrderDocument::class, 'orders_id','orders_id');
    }

    /**
     * @return OrderDocument|null
     */
    public function getCobreBemDocumentAttribute()
    {
        $document = null;

        if ($this->documents->count()) {
            $this->documents->each(function($doc) use (&$document) {
                if ($doc->type == 1) {
                    $document = $doc;
                }
            });
        }

        return $document;
    }

    /**
     * @return string
     */
    public function getGatewaySpecificUrlAttribute()
    {
        if ($this->gateway instanceof GatewayProfile) {
            if ($this->gateway->account_id == GatewayAccount::COBRE_BEM) {
                if ($this->payment_method == 'boleto') {
                    if ($field = $this->gateway
                        ->fields()
                        ->where('name', 'Boleto Link and Explanation')
                        ->first()
                    ) {
                        $hash = '';

                        if ($doc = $this->combre_bem_document) {
                            $hash = $doc->hash;
                        }

                        $docUrl = HTTPS_SERVER . "/order_documentation.php?orderId={$this->id}&type=1&access_token={$hash}";

                        return strtr($field->value, ['{link}' => urlencode($docUrl)],);
                    }
                }
            }
        }

        return '';
    }

    public function getAllProductIdsAttribute()
    {
        $ids = [];

        foreach ($this->products as $product) {
            $ids[] = $product->product_id;
        }

        return $ids;
    }

    /**
     * @return string[]
     */
    public function getGiroAttribute()
    {
        $fields = [
            'Account Name'   => '',
            'Bank Name'      => '',
            'Bank Address'   => '',
            'Sort Code'      => '',
            'Account Number' => '',
            'IBAN'           => '',
            'Swift'          => '',
        ];

        if ($this->payment_method == 'giro' && $this->gateway instanceof GatewayProfile) {
            $this->gateway
                ->fields
                ->each(function($field) use (&$giro, $fields) {
                    if (array_key_exists($field->name, $fields)) {
                        $fields[$field->name] = $field->value;
                    }
                });
        }

        foreach ($fields as $field => $value) {
            $fields[Str::camel($field)] = $value;
            unset($fields[$field]);
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function getStatusAttribute()
    {
        $status = 'unknown';

        switch ($this->status_id) {
            case OrderStatus::STATUS_NEW:
                $status = 'new';
            break;
            case OrderStatus::STATUS_APPROVED:
                $status = 'approved';
            break;
            case OrderStatus::STATUS_REFUNDED:
                switch ($this->refund_type_id) {
                    case self::PARTIALLY_REFUNDED:
                        $status = 'partially refunded';
                    break;
                    case self::FULLY_REFUNDED:
                        $status = 'fully refunded';
                    break;
                    case self::VOIDED:
                        $status = 'voided';
                    break;
                    case self::REFUND_REVERSED:
                        $status = 'reversed';
                    break;
                }
            break;
            case OrderStatus::STATUS_DECLINED:
                $status = 'declined';
            break;
            case OrderStatus::STATUS_SHIPPED:
                $status = 'shipped';
            break;
            case OrderStatus::STATUS_PENDING:
                $status = 'pending';
            break;
        }

        return $status;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status_id == OrderStatus::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function getIsActiveSubscriptionAttribute()
    {
        return ! $this->is_archived && $this->is_recurring && ! $this->is_hold;
    }

    /**
     * @return bool
     */
    public function getIsCancelledAttribute()
    {
        return ($this->is_hold && ! $this->is_hold_from_recurring);
    }

    /**
     * @return object
     */
    public function getSubscriptionAttribute()
    {
        $subscription = [
            'ancestor_id'  => $this->ancestor_id,
            'parent_id'    => $this->parent_id,
            'is_recurring' => (int) $this->is_active_subscription,
        ];

        if ($this->is_active_subscription) {
            $subscription['depth']      = $this->rebill_depth;
            $subscription['attempt_at'] = $this->retry_at->year < 0 ? $this->recur_at : $this->retry_at;
            $subscription['discount']   = $this->rebill_discount;
            $subscription['forecasted'] = $this->forecasted_revenue;

            if ($this->custom_rec_prod_id) {
                $subscription['custom_next_product_id'] = $this->custom_rec_prod_id;
            }

            if ($this->custom_variant_id) {
                $subscription['custom_next_variant_id'] = $this->custom_variant_id;
            }

            if ($this->custom_subscription) {
                $subscription['custom_subscription'] = $this->custom_subscription;
            }

            if ($this->is_stop_next_recur) {
                $subscription['is_stop_after_next_success'] = $this->is_stop_next_recur;
            }
        } else {
            $subscription['status'] = $this->is_cancelled ? 'canceled' : 'hold';
        }

        if ($this->retry_attempt_no) {
            $subscription['retry'] = $this->retry;
        }

        return (object) $subscription;
    }

    /**
     * @return object
     */
    public function getRetryAttribute()
    {
        return (object) [
            'attempt_no'   => $this->retry_attempt_no,
            'discount_pct' => $this->retry_discount_pct,
            'discount_amt' => $this->retry_discount_amt,
            'attempt_at'   => $this->retry_at,
        ];
    }

    /**
     * @return object
     */
    public function getConfirmationAttribute()
    {
        return (object) [
            'is_confirmed' => $this->is_confirmed,
            'confirmed_at' => $this->confirmed_at,
            'id'           => $this->confirmation_id,
            'status'       => $this->confirmation_status,
        ];
    }

    /**
     * @return object
     */
    public function getFulfillmentAttribute()
    {
        return (object) [
            'is_shippable'        => $this->is_shippable,
            'is_shipped'          => $this->is_shipped,
            'is_posted'           => $this->is_fulfillment_posted,
            'is_tracking_fetched' => $this->is_tracking_posted,
            'shipped_at'          => $this->is_shipped ? $this->shipped_at : null,
        ];
    }

    /**
     * @return string
     */
    public function getFulfillmentNumberAttribute()
    {
        if ($oa = $this->fulfillment_number()->first()) {
            return $oa->value;
        } elseif ($number = $this->attributes['fulfillmentNumber']) {
            return $number;
        }

        return '';
    }

    /**
     * @return object
     */
    public function getShippingAttribute()
    {
        return (object) [
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'address'    => $this->address,
            'address2'   => $this->address2,
            'city'       => $this->city,
            'state'      => $this->state,
            'state_id'   => $this->state_id,
            'zip'        => $this->zip,
            'country_id' => $this->country_id,
            'country'    => $this->ship_country,
        ];
    }

    /**
     * @return object
     */
    public function getBillingAttribute()
    {
        return (object) [
            'first_name' => $this->bill_first_name,
            'last_name'  => $this->bill_last_name,
            'address'    => $this->bill_address,
            'address2'   => $this->bill_address2,
            'city'       => $this->bill_city,
            'zip'        => $this->bill_zip,
            'state'      => $this->bill_state,
            'state_id'   => $this->bill_state_id,
            'country_id' => $this->bill_country_id,
            'country'    => $this->bill_country,
        ];
    }

    /**
     * @return object
     */
    public function getPaymentAttribute()
    {
        $force = false;

        switch (true) {
            case $this->payment_method == 'checking':
                $type    = 'checking';
                $type_id = PaymentType::TYPE_CHECKING;
            break;
            case (! in_array($this->payment_method, (new \alt_pay_providers)->alt_pay_providers_all)):
                $type    = 'credit card';
                $type_id = PaymentType::TYPE_CREDIT_CARD;
            break;
            default:
                $type_id = 0;
                $type    = 'other';
        }

        $payment = [
            'type'                => $type,
            'method'              => $this->payment_method,
            'gateway_id'          => $this->gateway_id,
            'is_preserve_gateway' => $this->is_preserve_gateway,
            'payment_type_id'     => $type_id,
        ];

        if ($this->check_force != null) {
            $force = $this->check_force;
        } elseif ($this->cascade_force != null) {
            $force = $this->cascade_force;
        } elseif ($this->gateway_force != null) {
            $force = $this->gateway_force;
        }

        if ($force) {
            $payment['is_force'] = true;
            $payment['force']    = $force;
        }

        if ($type == 'credit card') {
            $payment['first_6'] = $this->cc_first_6;
            $payment['last_4']  = $this->cc_last_4;
            $payment['expiry']  = $this->cc_expiry;
        }

        return (object) $payment;
    }

    /**
     * @return object
     */
    public function getAffiliateAttribute()
    {
        return (object) [
            'click_id' => $this->click_id,
            'afid'     => $this->AFID,
            'sid'      => $this->SID,
            'affid'    => $this->AFFID,
            'c1'       => $this->C1,
            'c2'       => $this->C2,
            'c3'       => $this->C3,
            'aid'      => $this->AID,
        ];
    }

    /**
     * @return string
     */
    protected function getUtmCampaignAttribute()
    {
        return $this->utm->campaign;
    }

    /**
     * @return string
     */
    protected function getUtmContentAttribute()
    {
        return $this->utm->content;
    }

    /**
     * @return string
     */
    protected function getUtmDeviceCategoryAttribute()
    {
        return $this->utm->device_category;
    }

    /**
     * @return string
     */
    protected function getUtmMediumAttribute()
    {
        return $this->utm->medium;
    }

    /**
     * @return string
     */
    protected function getUtmSourceAttribute()
    {
        return $this->utm->source;
    }

    /**
     * @return string
     */
    protected function getUtmTermAttribute()
    {
        return $this->utm->term;
    }

    /**
     * @return string
     */
    protected function getSubAffiliateAttribute()
    {
        if ($this->AFID != '') {
            return $this->SID;
        } else if ($this->AFFID != '') {
            return "{$this->C1},{$this->C2},{$this->C3}";
        } else {
            return ($this->AID != '' ? $this->OPT : '');
        }
    }

    /**
     * @return string
     */
    protected function getAllSubscriptionIdCsvAttribute()
    {
        $result = [];

        $this->allItems->each(function ($item) use (&$result) {
            $result[$item->order_product->product_id] = $item->subscription_id;
        });

        return implode(',', $result);
    }

    /**
     * @return string
     */
    protected function getAllSubscriptionActiveCsvAttribute()
    {
        $result = [];

        $this->allItems->each(function ($item) use (&$result) {
            $result[$item->order_product->product_id] = $item->is_recurring;
        });

        return implode(',', $result);
    }

    /**
     * @return string
     */
    protected function getShippingIdAttribute()
    {
        return $this->ship_method->id;
    }

    /**
     * @return string
     */
    protected function getShippingMethodNameAttribute()
    {
        return $this->ship_method->name;
    }

    /**
     * @return string
     */
    protected function getShippingGroupNameAttribute()
    {
        return $this->ship_method->type->name;
    }

    /**
     * @return string
     */
    protected function getAllProductIdCsvAttribute()
    {
        return implode(',', $this->products->pluck('product_id')->toArray());
    }

    /**
     * @return string
     */
    protected function getAllProductNameCsvAttribute()
    {
        return implode(',', $this->products->pluck('product_name')->toArray());
    }

    /**
     * @return string
     */
    protected function getAllProductPriceCsvAttribute()
    {
        return implode(',', $this->products->pluck('price')->toArray());
    }

    /**
     * @return string
     */
    protected function getAllProductQtyCsvAttribute()
    {
        return implode(',', $this->products->pluck('quantity')->toArray());
    }

    /**
     * @return string
     */
    protected function getAllProductSkuCsvAttribute()
    {
        return implode(',', $this->products->pluck('product_sku')->toArray());
    }

    /**
     * @return string
     */
    protected function getCcTypeUcaseAttribute()
    {
        return strtoupper($this->payment_method);
    }

    /**
     * @return string
     */
    protected function getAffiliateTokenAttribute()
    {
        return $this->AFID ?? $this->AFFID ?? $this->AID ?? '';
    }

    /**
     * @return mixed
     */
    protected function getCurrencyCodeAttribute()
    {
        return $this->currency->code;
    }

    /**
     * @return mixed
     */
    protected function getIsGiftAttribute()
    {
        return $this->gift()->exists();
    }

    /**
     * @return \Carbon\Carbon
     */
    protected function getCarbonTimeStamp()
    {
        return new Carbon($this->created_at);
    }

    /**
     * @return mixed
     */
    protected function getAmericanTimestampAttribute()
    {
        return $this->getCarbonTimeStamp()->format('m/d/Y H:i:s');
    }

    /**
     * @return mixed
     */
    protected function getAmericanDateAttribute()
    {
        return $this->getCarbonTimeStamp()->format('m/d/Y');
    }

    /**
     * @return int
     */
    protected function getPostBackOrderStatusAttribute()
    {
        return (int) ($this->status_id != 7);
    }

    /**
     * @return mixed
     */
    protected function getOrderTotalAttribute()
    {
        return $this->total->value;
    }

    /**
     * @return mixed
     */
    protected function getTaxableAmountAttribute()
    {
        return $this->taxable_total->value;
    }

    /**
     * @return mixed
     */
    protected function getTaxFactorAttribute()
    {
        return $this->tax_total->value;
    }

    /**
     * @return mixed
     */
    protected function getMainProductAmountUpsellTaxAttribute()
    {
        return $this->total_revenue - $this->shipping_amount->value;
    }

    /**
     * @return string
     */
    protected function getDeclineReasonAttribute()
    {
        $status = '';

        if ($this->status_id == 7) {
            if ($declineRecord = $this->history_notes()
                ->whereIn('type', [
                    'order-process',
                    'history-note-checking-disabled',
                    'manual-reprocess-fail',
                    'fraud-screening-failed',
                    'start-recurring-fail',
                    'fraud-screening-failed',
                    'force-bill-error',
                ])
                ->where('status', 'not like', 'OrdersId%')
                ->orderBy('t_stamp', 'DESC')
                ->first()) {
                $status = $declineRecord->status;
            }
        }

        return $status;
    }

    /**
     * Get the main order and all of its upsells in one collection
     * @return collection
     * @deprecated use getAllOrderItemsAttribute, see https://sticky.atlassian.net/browse/DEV-1358
     */
    protected function getAllItemsAttribute()
    {
        return $this->all_order_items;
    }

    /**
     * Get the main order and all of its upsells in one collection
     * @return Collection
     */
    protected function getAllOrderItemsAttribute(): Collection
    {
        return collect([$this])->merge($this->additional_products);
    }

    /**
     * Get the recurring main order and all of it's recurring upsells in one collection
     * @return collection
     */
    public function getAllRecurringItemsAttribute(): Collection
    {
        $items = collect();
        if ($this->all_order_items) {
            $items = $this->all_order_items->filter(static function (Subscription $item) {
                return $item->next_valid_recurring_date;
            });
        }
        return $items;
    }

    /**
     * Check if the order has a recurring main order or any of it's upsells are recurring
     * @return bool
     */
    public function getHasRecurringItemsAttribute(): bool
    {
        return $this->all_recurring_items && $this->all_recurring_items->count() > 0;
    }

    /**
     * @return object
     */
    public function getLineItemsAttribute()
    {
        return (object) [
            'total'           => (float) $this->total_revenue,
            'subtotal'        => (float) $this->subtotal->value + $this->additional_revenue,
            'shipping'        => (float) $this->shipping_amount->value,
            'tax'             => (float) $this->tax_amount->value,
            'tax_pct'         => (float) $this->tax_percent->value,
            'vat_tax'         => (float) $this->vat_tax_amount->value,
            'vat_tax_pct'     => (float) $this->vat_tax_percent->value,
            'volume_discount' => (float) $this->volume_discount->value,
            'restocking_fee'  => (float) $this->restocking_fee->value,
            'amount_refunded' => (float) $this->amount_refunded,
            'refunded'        => (int) ($this->amount_refunded > 0),
        ];
    }

    /**
     * @return Model|HasOne|object|null
     */
    protected function getGatewayAttribute()
    {
        if ($this->payment_method == 'offline') {
            return null;
        }

        return $this->gateway()->first();
    }

    /**
     * @return HasOne|null
     */
    public function gateway()
    {
        if ($this->payment_method == 'checking') {
            return $this->hasOne(CheckingProfile::class, 'checkProviderId', 'gatewayId');
        }

        return $this->hasOne(GatewayProfile::class, 'gateway_id', 'gatewayId')
           ->withoutGlobalScope(ActiveScope::class);
    }

    /**
     * @return Carbon
     */
    public function getAttemptAtAttribute()
    {
        return Carbon::parse($this->retry_at->year > 0 ? $this->retry_at : $this->recur_at);
    }

    /**
     * @return HasOne
     */
    public function cascade_force()
    {
        return $this->hasOne(CascadeForce::class, 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function gateway_force()
    {
        return $this->hasOne(GatewayForce::class, 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function check_force()
    {
        return $this->hasOne(CheckForce::class, 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function next_recurring_gateway()
    {
        return $this->hasOne(NextRecurringGateway::class);
    }

    /**
     * @return HasMany
     */
    public function product_licenses()
    {
        return $this->hasMany(ProductLicense::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function origin_id()
    {
        return $this->hasOne(OriginTypeId::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function gift()
    {
        return $this->hasOne(GiftOrder::class, 'order_id', 'orders_id')
            ->where('type_id', OrderSubscription::TYPE_MAIN);
    }

    /**
     * @return mixed|null
     */
    public function getOriginIdAttribute()
    {
        if ($origin_id = $this->origin_id()->first()) {
            return $origin_id->value;
        }

        return null;
    }

    /**
     * @return HasOne|ConsentRequired
     */
    public function consent_required(): HasOne
    {
        return $this->hasOne(ConsentRequired::class, 'order_id', 'orders_id');
    }


   /**
    * @return HasOne|ConsentWorkflowType
    */
   public function consent_workflow_type(): HasOne
   {
      return $this->hasOne(ConsentWorkflowType::class, 'order_id', 'orders_id');
   }

   /**
    * @return bool
    */
   public function getUsesPaysafeConsentAttribute(): bool
   {
      return $this->consent_workflow_type && $this->consent_workflow_type->value === ConsentWorkflowType::PAYSAFE_CONSENT_TYPE;
   }

    /**
     * @return bool
     */
    public function getAncestorUsesPaysafeConsentAttribute(): bool
    {
        return $this->common_ancestor && $this->common_ancestor->uses_paysafe_consent;
    }

    /**
     * @return bool
     */
    public function getUsesInternalConsentAttribute(): bool
    {
        return $this->consent_workflow_type && $this->consent_workflow_type->value === ConsentWorkflowType::INTERNAL_CONSENT_TYPE;
    }

   /**
    * @return bool
    */
   public function getAncestorUsesInternalConsentAttribute(): bool
   {
       return $this->common_ancestor && $this->common_ancestor->uses_internal_consent;
   }

   /**
    * @return bool
    */
   public function getIsConsentRequiredAttribute()
   {
      return $this->consent_required()
         ->where('value', 1)
         ->exists();
   }

    /**
     * @return HasOne
     */
    public function skip_fulfillment()
    {
        return $this->hasOne(SkipFulfillmentPost::class, 'order_id', 'orders_id');
    }

    /**
     * @return bool
     */
    public function getSkipFulfillmentAttribute()
    {
        return $this->skip_fulfillment()
            ->where('value', 1)
            ->exists();
    }

    /**
     * @return HasOne
     */
    public function bad_shipping_address()
    {
        return $this->hasOne(BadShippingAddress::class, 'order_id', 'orders_id');
    }

    /**
     * @return bool
     */
    public function getIsBadShippingAddressAttribute()
    {
        return $this->bad_shipping_address()
            ->where('value', 1)
            ->exists();
    }

    /**
     * @return HasOne
     */
    public function consent()
    {
        return $this->hasOne(OrderConsent::class, 'order_id', 'orders_id');
    }

    /**
     * @return bool
     */
    public function getHasConsentAttribute()
    {
        return $this->consent()->exists();
    }

    /**
     * @return HasMany
     */
    public function email_logs()
    {
        return $this->hasMany(EmailLog::class, 'order_id', 'orders_id');
    }

    /**
     * @return mixed
     */
    public function getHasSentConsentNotificationAttribute()
    {
        return $this->email_logs()->ofType(ConsentRequested::TYPE_ID)->exists();
    }

    /**
     * @return HasOne|self
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'orders_id', 'parent_order_id');
    }
    /**
     * @return HasOne|self
     */
    public function common_ancestor(): HasOne
    {
        return $this->hasOne(self::class, 'orders_id', 'commonAncestorOrderId');
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_order_id', 'orders_id');
    }

    /**
     * @return HasMany
     */
    public function siblings()
    {
        return $this->hasMany(self::class, 'parent_order_id', 'parent_order_id')
            ->where('parent_order_id', '!=', 0)
            ->where('rebillDepth', '>', 0);
    }

    /**
     * @return HasMany
     */
    public function ancestor_children(): HasMany
    {
        return $this->hasMany(self::class, 'commonAncestorOrderId', 'commonAncestorOrderId')
            ->where('parent_order_id', '!=', 0)
            ->where('rebillDepth', '>', 0);
    }

    /**
     * @return array
     */
    public function getCustomFieldsForLegacyAttribute()
    {
        $legacy = [];

        foreach ($this->getAttribute('custom_fields') as $field) {
            $legacy[] = $field->toArray();
        }

        return $legacy;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function custom_fields()
    {
        $custom_fields = CustomField::on($this->getConnectionName())
            ->where('entity_type_id', '=', $this->entity_type_id)
            ->get();

        foreach ($custom_fields as $custom_field) {
            $custom_field->entity_id = $this->getAttribute('id');
            $custom_field->setAppends([
                'type_id',
                'values',
            ])->makeVisible([
                'values',
            ]);
        }

        $real_custom_fields = $custom_fields
            ->filter(function ($field) {
                return (bool) $field->values->count();
            })
            ->values()
            ->all();

        $this->setRelation('custom_fields', $real_custom_fields);

        return $real_custom_fields;
    }

    /**
     * @param $tokenKey
     * @return bool
     */
    public function hasCustomField($tokenKey)
    {
        static $cache;

        if (in_array($tokenKey, $cache)) {
            return true;
        }

        if ($exists = count($this->custom_fields) && collect($this->custom_fields)->where('token_key', $tokenKey)->count() > 0) {
            array_push($cache, $tokenKey);
        }

        return $exists;
    }

    /**
     * @param $tokenKey
     * @return Collection | null
     */
    public function getCustomFieldValues($tokenKey)
    {
        if ($this->hasCustomField($tokenKey)) {
            return collect($this->custom_fields)->where('token_key', $tokenKey)->first()->values;
        }

        return null;
    }

    /**
     * @param      $data
     * @param null $byToken
     * @return mixed
     */
    public function addCustomFieldValue($data, $byToken = null)
    {
        $data = array_merge([
            'custom_field_id' => null,
            'entity_type_id'  => self::ENTITY_ID,
            'entity_id'       => $this->id,
            'option_id'       => 0,
            'created_by'      => 0,
            'value'           => '',
        ], $data);

        if ($byToken) {
            $data['custom_field_id'] = CustomField::ofEntityType(self::ENTITY_ID)
                ->where('token_key', $byToken)
                ->firstOrFail()
                ->id;
        }

        $uniqueKeys = ['custom_field_id', 'entity_type_id', 'entity_id'];

        return CustomFieldValue::updateOrCreate(Arr::only($data, $uniqueKeys), Arr::except($data, $uniqueKeys));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getCustomFieldsAttribute()
    {
        return (array_key_exists('custom_fields', $this->relations))
            ? $this->getRelation('custom_fields')
            : $this->custom_fields();
    }

    /**
     * @param $productId
     * @return mixed|null
     */
    public function getProductById($productId)
    {
        foreach ($this->products as $product) {
            if ($productId == $product->product_id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsAttribute()
    {
        if (! isset($this->attributes['products'])) {
            $this->attributes['products'] = $this->order_product()->get();

            if (count($products = $this->additional_products()->get())) {
                foreach ($products as $product) {
                    $this->attributes['products'] = $this->attributes['products']->merge($product->order_product()->get());
                }
            }
        }

        return $this->attributes['products'];
    }

    /**
     * @return bool
     */
    public function hasProductWithStep()
    {
        foreach ($this->getAttribute('products') as $product) {
            if ($product->step_num) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return HasMany
     */
    public function history_notes()
    {
        return $this->hasMany(OrderHistoryNote::class, 'orders_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoryNotesAttribute()
    {
        return $this->history_notes()->get();
    }

    /**
     * @return boolean
     */
    public function canRebill()
    {
        return (! $this->is_consent_required || $this->has_consent);
    }

    /**
     * @return HasOne
     */
    public function total()
    {
        return $this->hasOne(OrderLineItems\Total::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function subtotal()
    {
        return $this->hasOne(OrderLineItems\SubTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function shipping_amount()
    {
        return $this->hasOne(OrderLineItems\ShippingTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function tax_amount()
    {
        return $this->hasOne(OrderLineItems\TaxTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function vat_tax_amount()
    {
        return $this->hasOne(OrderLineItems\VatTaxTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * Get the volume discount amount.
     * @return HasOne
     */
    public function volume_discount()
    {
        return $this->hasOne(OrderLineItems\VolumeDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function non_taxable_total()
    {
        return $this->hasOne(OrderLineItems\NonTaxableTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function taxable_total()
    {
        return $this->hasOne(OrderLineItems\TaxableTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function tax_percent()
    {
        return $this->hasOne(OrderLineItems\TaxPct::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function vat_tax_percent()
    {
        return $this->hasOne(OrderLineItems\VatTaxPct::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function restocking_fee()
    {
        return $this->hasOne(OrderLineItems\RestockingFee::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function shipping_coupon_discount()
    {
        return $this->hasOne(OrderLineItems\CouponDiscountShippingTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function coupon_discount_total()
    {
        return $this->hasOne(OrderLineItems\CouponDiscountTotal::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function step_down_discount()
    {
        return $this->hasOne(OrderLineItems\StepDownDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function shipping_step_down_discount()
    {
        return $this->hasOne(OrderLineItems\ShippingStepDownDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function prepaid_discount()
    {
        return $this->hasOne(OrderLineItems\PrepaidDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function rebill_discount_amount(): HasOne
    {
        return $this->hasOne(OrderLineItems\RebillDiscount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return float
     */
    public function getCouponTotalAttribute()
    {
        return (float) $this->coupon_discount_total->value;
    }

    public function getBillingModelDiscountTotalAttribute()
    {
        $total = 0.00;

        $this->products
            ->each(function ($product) use (&$total) {
                $total += $product->billing_model_discount->value;
            });

        return $total;
    }

    public function getBillingModelSubscriptionCreditTotalAttribute()
    {
        $total = 0.00;

        $this->products
            ->each(function ($product) use (&$total) {
                $total += $product->billing_model_subscription_credit->value;
            });

        return $total;
    }

    /**
     * @return float
     */
    public function getRebillDiscountTotalAttribute(): float
    {
        return (float) $this->rebill_discount_amount->value;
    }

    /**
     * @return mixed
     */
    public function getTotalDiscountAttribute()
    {
        return $this->coupon_total +
            $this->shipping_coupon_discount->value +
            $this->shipping_step_down_discount->value +
            $this->step_down_discount->value +
            $this->billing_model_subscription_credit_total +
            $this->billing_model_discount_total +
            $this->rebill_discount_total +
            $this->prepaid_discount->value;
    }

    /**
     * @return mixed
     */
    public function getOrderLevelDiscountTotalAttribute()
    {
        return $this->step_down_discount->value +
            $this->rebill_discount_total +
            $this->prepaid_discount->value;
    }

    /**
     * @return float
     */
    public function getOrderTotalFinalAttribute()
    {
        return $this->total_revenue;
    }

    /**
     * @return float
     */
    protected function getNonTaxableTotalAttribute()
    {
        return $this->non_taxable_total->value;
    }

    /**
     * @return float
     */
    public function getTotalRevenueAttribute()
    {
        return $this->subtotal->value +
            $this->shipping_amount->value +
            $this->tax_amount->value +
            $this->vat_tax_amount->value +
            $this->additional_revenue;
    }

    /**
     * @return float
     */
    public function getAdditionalRevenueAttribute()
    {
        $total = 0.0;

        if ($this->additional_products->count()) {
            foreach ($this->additional_products as $product) {
                $total += $product->subtotal->value;
            }
        }

        return $total;
    }

    /**
     * @return void
     */
    public function cancelOrder(): void
    {
        $userId             = get_current_user_id();
        $holdDate           = date("Y-m-d");
        $this->is_hold      = 1;
        $this->hold_date    = $holdDate;
        $this->is_recurring = 0;
        $this->save();
        $this->history_notes()
            ->createMany([
                [
                    'user_id'   => $userId,
                    'type_name' => 'recurring',
                    'message'   => 'stop',
                ],
                [
                    'user_id'   => $userId,
                    'type_name' => 'recurring',
                    'message'   => 'hold',
                ],
            ]);

        Event::dispatch(new \App\Events\Order\SubscriptionCancelled(
            $this,
            $this->subscription_id,
            $this->order_product->product_id
        ));

        if ($this->additional_products->count()) {
            foreach ($this->additional_products as $upsell) {
                $upsell->is_hold      = 1;
                $upsell->hold_date    = $holdDate;
                $upsell->is_recurring = 0;
                $upsell->save();
                $this->history_notes()
                    ->create([
                        'user_id'   => $userId,
                        'type_name' => 'recurring-upsell-stopped',
                        'message'   => $upsell->id,
                    ]);
                Event::dispatch(new \App\Events\Order\SubscriptionCancelled(
                    $this,
                    $upsell->subscription_id,
                    $upsell->order_product->product_id
                ));
            }
        }
    }

    /**
     * @return object
     */
    public function getSubtotalAttribute()
    {
        return $this->subtotal()->first();
    }

    /**
     * @return object
     */
    public function subscriptionCreditTotal()
    {
        return $this->hasOne(OrderLineItems\SubscriptionCredit::class, 'orders_id', 'orders_id');
    }

    /**
     * @return object
     */
    public function getSubscriptionCreditTotalAttribute()
    {
        return $this->subscriptionCreditTotal()->first();
    }

    /**
     * @return int
     */
    public function getAncestorOrSelfAttribute()
    {
        return $this->commonAncestorOrderId ? : $this->orders_id;
    }

    /**
     * @param      $referrer
     * @param int  $userId
     * @param int  $apiUserId
     * @param bool $consentedDate Date that the customer consented directly with Paysafe, in the `2020-12-02T18:54:42.429Z` format
     * @throws ConsentAlreadyAppliedException
     * @throws ConsentNotRequiredException
     * @throws ConsentWithoutNotificationException
     * @throws ProviderActionNotAllowed
     */
    public function applyConsent($referrer, $userId = 0, $apiUserId = 0, $consentedDate = false)
    {
        $isUsingConsentService = \system_module_control::check('USE_CONSENT_SERVICE');

        if ($isUsingConsentService) {
            $userId = User::SYSTEM;
            $apiUserId = User::API;
        }

        $consentCreatedAt = null;
        switch (true) {
            case !$this->is_consent_required:
                if ($consentedDate) {
                    // if we have a $consentedDate, it came from Paysafe
                    // if consent is not required, it's already been applied
                    // just return here
                    return null;
                }
                // if no $consentedDate and consent is required, ie it's already been applied or didn't need it
                // something is not right, throw an exception here
                throw new ConsentNotRequiredException('Paysafe consent not required error');
            case $this->has_consent:
                throw new ConsentAlreadyAppliedException('Paysafe consent already applied error');
            case $consentedDate && $this->uses_paysafe_consent:
                $consentCreatedAt = Carbon::parse($consentedDate)->toDateTimeString();
                break;
            case !$isUsingConsentService && !$this->has_sent_consent_notification && !$this->uses_paysafe_consent:
                throw new ConsentWithoutNotificationException('Paysafe consent without notification error');
            case $this->is_paysafe_gateway:
                if (!$isUsingConsentService && ((($userId + $apiUserId) > 0) || $this->uses_paysafe_consent)) {
                    throw new ProviderActionNotAllowed('Paysafe provider action not allowed error');
                }
        }

        try {
            $consentData = [
                'order_id'              => $this->orders_id,
                'ip_address'            => \get_ip_address_from_post(),
                'api_user_id'           => $apiUserId,
                'user_id'               => $userId,
                'http_referrer'         => $referrer,
                'order_consent_type_id' => ($apiUserId ? OrderConsentType::TYPE_ID_API : OrderConsentType::TYPE_ID_CALL),
            ];
            if ($consentCreatedAt) {
                $consentData['created_at'] = $consentCreatedAt;
            }
            OrderConsent::create($consentData);
        } catch (QueryException $e) {
            if ($e->getCode() === 23000) {
                throw new ConsentAlreadyAppliedException('Paysafe consent already applied error');
            }

            throw new QueryException($e->getSql(), $e->getBindings(), $e);
        }
    }

    /**
     * Cancel consent to rebill the order/subscription
     * @return bool
     */
    public function cancelConsent(): bool
    {
        $success = false;
        try {
           $success = $this->stopRecurring();
        } catch (\Exception $e) {
            // do nothing, logging etc has already occurred
        }
        $status = $success ? \nmi_paysafe::CONSENT_HISTORY_STATUS_CANCELLED : \nmi_paysafe::CONSENT_HISTORY_STATUS_CANCEL_FAILED;
        $this->addHistoryNote(\nmi_paysafe::CONSENT_HISTORY_TYPE, $status);

        return $success;
    }

    /**
     * @return bool
     */
    public function hasShippableProducts() :bool
    {
        return ($this->getShippableProductCount(true) > 0);
    }

    /**
     * @param bool $returnFirstOne
     * @return int
     */
    public function getShippableProductCount($returnFirstOne = false) :int
    {
        $shippableProductCount = 0;

        if ($this->isShippable()) {
            $shippableProductCount++;

            if ($returnFirstOne) {
                return 1;
            }
        }

        if (count($products = $this->additional_products()->get())) {
            foreach ($products as $upsell) {
                if ($upsell->isShippable()) {
                    $shippableProductCount++;

                    if ($returnFirstOne) {
                        return 1;
                    }
                }
            }
        }

        return $shippableProductCount;
    }

    /**
     * @return mixed
     */
    public function getOrderIdAttribute()
    {
        return $this->getAttribute('id');
    }

    /**
     * @return bool
     */
    protected function getIsCheckingAttribute()
    {
        return ($this->payment_method == 'checking');
    }

    /**
     * @return mixed
     */
    public function getMainOrderIdAttribute()
    {
        return $this->getAttribute('id');
    }

    /**
     * @return bool
     */
    public function getIsRebillAttribute()
    {
        return $this->rebill_depth > 0;
    }

    /**
     * @return bool
     */
    public function getIsFirstRebillAttribute()
    {
        return $this->rebill_depth == 1;
    }

    /**
     * @return string
     */
    public function getSmsPhoneAttribute()
    {
        return $this->phone;
    }

    /**
     * @return HasOne
     */
    public function subscription_credit()
    {
        return $this->hasOne(SubscriptionCredit::class, 'item_id', 'subscription_id');
    }

    /**
     * @return HasOne
     */
    public function utm()
    {
        return $this->hasOne(OrderUtm::class, 'order_id', 'orders_id');
    }

    /**
     * @param int $newOrderId
     */
    public static function stopTerminalProducts($newOrderId = 0)
    {
        if ($newOrderId) {
            if ($order = (new self)->find($newOrderId)) {
                if ($parent = $order->parent) {
                    $terminatingSubscriptions = [];
                    $parentProducts           = $parent->products;

                    foreach ($parentProducts as $product) {
                        if ($product->is_terminal) {
                            $terminatingSubscriptions[] = $product->order->subscription_id;
                        }
                    }

                    if (count($terminatingSubscriptions)) {
                        $now             = Carbon::now();
                        $user            = get_current_user_id();
                        $currentProducts = $order->products;

                        foreach ($terminatingSubscriptions as $terminator) {
                            foreach ($currentProducts as $product) {
                                $tempOrder = $product->order;

                                if ($tempOrder->subscription_id == $terminator) {
                                    if ($tempOrder->is_recurring) {
                                        $product->update([
                                            'hold_type_id' => SubscriptionHoldType::USER,
                                        ]);
                                        $tempOrder->update([
                                            'is_recurring' => 0,
                                            'is_hold'      => 1,
                                            'hold_date'    => $now,
                                        ]);

                                        if ($tempOrder instanceof self) {
                                            $order->history_notes()
                                                ->createMany([
                                                    [
                                                        'user_id'   => $user,
                                                        'type_name' => 'recurring',
                                                        'message'   => 'stop',
                                                    ],
                                                    [
                                                        'user_id'   => $user,
                                                        'type_name' => 'recurring',
                                                        'message'   => 'hold',
                                                    ],
                                                ]);
                                        } else {
                                            $order->history_notes()
                                                ->create([
                                                    'user_id'   => $user,
                                                    'type_name' => 'recurring-upsell-stopped',
                                                    'message'   => $tempOrder->id,
                                                ]);
                                        }

                                        Event::dispatch(new \App\Events\Order\SubscriptionCancelled(
                                            $order,
                                            $tempOrder->subscription_id,
                                            $product->product_id
                                        ));
                                        \commonProviderUpdateOrder($newOrderId, 'cancel');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $orderId
     */
    public static function relateContact($orderId)
    {
        try {
            if ($order = self::withoutGlobalScopes()->find($orderId)) {
                if ($order->contact) {
                    $exists = $order->contact->relationships()->where([
                        'entity_type_id' => Order::ENTITY_ID,
                        'entity_id'      => $orderId,
                    ])->exists();

                    if (! $exists) {
                        $order->contact->relationships()->create([
                            'entity_type_id' => self::ENTITY_ID,
                            'entity_id'      => $orderId,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @param $item
     * @return bool
     */
    public function isBillingWith($item)
    {
        if ($this->is_active_subscription && $item->is_active_subscription) {
            return $item->attempt_at->lessThanOrEqualTo($this->attempt_at);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function swap()
    {
        $swapped = false;
        $this->swappedMainToUpsellId = null;

        try {
            $swapToMain = $this->additional_products()
                ->recurring()
                ->where('is_add_on', 0)
                ->orderBy('date_purchased', 'DESC')
                ->orderBy('recurring_date', 'DESC')
                ->first();

            if ($swapToMain) {
                DB::beginTransaction();

                // Swapping tax % for line items
                $uov1 = $swapToMain->line_item_tax->value ?? 0;
                $mov1 = $this->line_item_tax->value ?? 0;

                if ((float) $uov1 > 0 || (float) $mov1 > 0) {
                    $this->line_item_tax()->updateOrCreate([], ['value' => $uov1]);
                    $swapToMain->line_item_tax()->updateOrCreate([], ['value' => $mov1]);
                }

                // Swapping tax $ amount for line items
                $uota = $swapToMain->line_item_tax_amount->value ?? 0;
                $mota = $this->line_item_tax_amount->value ?? 0;

                if ((float) $uota > 0 || (float) $mota > 0) {
                    $this->line_item_tax_amount()->updateOrCreate([], ['value' => $uota]);
                    $swapToMain->line_item_tax_amount()->updateOrCreate([], ['value' => $mota]);
                }

                OrderProductBundle::where('bundle_id', $swapToMain->order_product->product_id)
                    ->where('order_id', $this->id)
                    ->where('is_main', 0)
                    ->update(['main_flag' => 1]);

                if ($swapToMain->subscription_order) {
                    if ($swapToMain->subscription_order->next_recurring_product != $swapToMain->order_product->product_id) {
                        OrderProductBundle::where('bundle_id', $swapToMain->subscription_order->next_recurring_product)
                            ->where('order_id', $this->id)
                            ->where('is_main', 0)
                            ->where('is_next_cycle', 1)
                            ->update(['main_flag' => 1]);
                    }

                    LineItemCustomOption::where('subscription_id', $swapToMain->subscription_id)
                       ->where('order_id', $swapToMain->id)
                       ->update([
                         'order_id'      => $this->id,
                         'order_type_id' => $this->getOrderTypeId()
                      ]);
                }

                OrderProductBundle::where('bundle_id', $this->order_product->product_id)
                    ->where('order_id', $this->id)
                    ->where('is_main', 1)
                    ->update(['main_flag' => 0]);

                if ($this->subscription_order) {
                    if ($this->subscription_order->next_recurring_product != $this->order_product->product_id) {
                        OrderProductBundle::where('bundle_id', $this->subscription_order->next_recurring_product)
                            ->where('order_id', $this->id)
                            ->where('is_main', 1)
                            ->where('is_next_cycle', 1)
                            ->update(['main_flag' => 0]);
                    }

                    LineItemCustomOption::where('subscription_id', $this->subscription_id)
                       ->where('order_id', $this->id)
                       ->update([
                         'order_id'      => $swapToMain->id,
                         'order_type_id' => $swapToMain->getOrderTypeId()
                      ]);
                }

                $mainHoldDate          = $this->hold_date;
                $upsellHoldDate        = $swapToMain->hold_date;
                $mainForecastRevenue   = $this->currency_value;
                $upsellForecastRevenue = $swapToMain->currency_value;

                $this->update([
                    'is_hold'        => 0,
                    'is_recurring'   => 1,
                    'hold_date'      => $upsellHoldDate,
                    'currency_value' => $upsellForecastRevenue,
                ]);
                $swapToMain->update([
                    'is_hold'        => 1,
                    'is_recurring'   => 0,
                    'hold_date'      => $mainHoldDate,
                    'currency_value' => $mainForecastRevenue,
                ]);

                $mainHoldTypeId   = $this->order_product->hold_type_id;
                $upsellHoldTypeId = $swapToMain->order_product->hold_type_id;

                $this->order_product->update([
                    'hold_type_id' => $upsellHoldTypeId,
                ]);
                $swapToMain->order_product->update([
                    'hold_type_id' => $mainHoldTypeId,
                ]);

                $orderSqlRaw = <<<'SQL'
UPDATE
      orders o,
      upsell_orders uo,
      orders_products op,
      upsell_orders_products uop,
      orders_total ots,
      orders_total ott,
      upsell_orders_total uots,
      upsell_orders_total uott
   SET
      o.recurring_date        = uo.recurring_date,
      uo.recurring_date       = ?,
      o.subscription_id       = uo.subscription_id,
      uo.subscription_id      = ?,
      op.products_id          = uop.products_id,
      uop.products_id         = op.products_id,
      op.products_name        = uop.products_name,
      uop.products_name       = op.products_name,
      op.products_price       = uop.products_price,
      uop.products_price      = op.products_price,
      op.products_quantity    = uop.products_quantity,
      uop.products_quantity   = op.products_quantity,
      op.refund_total         = uop.refund_total,
      uop.refund_total        = op.refund_total,
      op.return_quantity      = uop.return_quantity,
      uop.return_quantity     = op.return_quantity,
      op.return_reason_id     = uop.return_reason_id,
      uop.return_reason_id    = op.return_reason_id,
      op.fully_refunded_flag  = uop.fully_refunded_flag,
      uop.fully_refunded_flag = op.fully_refunded_flag,
      op.offer_id             = uop.offer_id,
      uop.offer_id            = op.offer_id,
      op.step_num             = uop.step_num,
      uop.step_num            = op.step_num,
      op.variant_id           = uop.variant_id,
      uop.variant_id          = op.variant_id,
      uots.value              = ots.value,
      uots.text               = ots.text,
      uott.value              = ott.value,
      uott.text               = ott.text,
      ots.value               = uots.value,
      ots.text                = uots.text
 WHERE
      o.orders_id = ?
   AND
      uo.upsell_orders_id = ?
   AND
      op.orders_id = ?
   AND
      uop.upsell_orders_id = ?
   AND
      ots.orders_id = ?
   AND
      ots.class = 'ot_subtotal'
   AND
      ott.orders_id = ?
   AND
      ott.class = 'ot_subtotal'
   AND
      uots.upsell_orders_id = ?
   AND
      uots.class = 'ot_subtotal'
   AND
      uott.upsell_orders_id = ?
   AND
      uott.class = 'ot_total'
SQL;
                $bmSqlRaw    = <<<'SQL'
UPDATE
      billing_model_order m
  JOIN
      billing_model_order s
   SET
      m.offer_id                         = s.offer_id,
      s.offer_id                         = m.offer_id,
      m.subscription_id                  = s.subscription_id,
      s.subscription_id                  = m.subscription_id,
      m.cycles_remaining                 = s.cycles_remaining,
      s.cycles_remaining                 = m.cycles_remaining,
      m.cycle_depth                      = s.cycle_depth,
      s.cycle_depth                      = m.cycle_depth,
      m.bill_by_type_id                  = s.bill_by_type_id,
      s.bill_by_type_id                  = m.bill_by_type_id,
      m.bill_by_days                     = s.bill_by_days,
      s.bill_by_days                     = m.bill_by_days,
      m.interval_day                     = s.interval_day,
      s.interval_day                     = m.interval_day,
      m.interval_week                    = s.interval_week,
      s.interval_week                    = m.interval_week,
      m.next_recurring_product           = s.next_recurring_product,
      s.next_recurring_product           = m.next_recurring_product,
      m.next_recurring_quantity          = s.next_recurring_quantity,
      s.next_recurring_quantity          = m.next_recurring_quantity,
      m.next_recurring_shipping          = s.next_recurring_shipping,
      s.next_recurring_shipping          = m.next_recurring_shipping,
      m.next_shipping_id                 = s.next_shipping_id,
      s.next_shipping_id                 = m.next_shipping_id,
      m.next_recurring_price             = s.next_recurring_price,
      s.next_recurring_price             = m.next_recurring_price,
      m.trial_flag                       = s.trial_flag,
      s.trial_flag                       = m.trial_flag,
      m.active                           = s.active,
      s.active                           = m.active,
      m.deleted                          = s.deleted,
      s.deleted                          = m.deleted,
      m.updated_by                       = s.updated_by,
      s.updated_by                       = m.updated_by,
      m.created_by                       = s.created_by,
      s.created_by                       = m.created_by,
      m.update_in                        = s.update_in,
      s.update_in                        = m.update_in,
      m.date_in                          = s.date_in,
      s.date_in                          = m.date_in,
      m.next_recurring_variant           = s.next_recurring_variant,
      s.next_recurring_variant           = m.next_recurring_variant,
      m.preserve_quantity                = s.preserve_quantity,
      m.preserve_quantity                = s.preserve_quantity,
      s.is_preserve_price                = m.is_preserve_price,
      s.is_preserve_price                = m.is_preserve_price,
      m.is_prepaid                       = s.is_prepaid,
      s.is_prepaid                       = m.is_prepaid,
      m.next_recurring_discount_amount   = s.next_recurring_discount_amount,
      s.next_recurring_discount_amount   = m.next_recurring_discount_amount,
      m.is_prepaid_subscription          = s.is_prepaid_subscription,
      s.is_prepaid_subscription          = m.is_prepaid_subscription,
      m.prepaid_cycles                   = s.prepaid_cycles,
      s.prepaid_cycles                   = m.prepaid_cycles,
      m.main_product_quantity            = s.main_product_quantity,
      s.main_product_quantity            = m.main_product_quantity,
      m.main_product_discount_type       = s.main_product_discount_type,
      s.main_product_discount_type       = m.main_product_discount_type,
      m.main_product_discount_amount     = s.main_product_discount_amount,
      s.main_product_discount_amount     = m.main_product_discount_amount,
      m.sticky_discount_percent          = s.sticky_discount_percent,
      s.sticky_discount_percent          = m.sticky_discount_percent,
      m.sticky_discount_flat_amount      = s.sticky_discount_flat_amount,
      s.sticky_discount_flat_amount      = m.sticky_discount_flat_amount,
      m.current_prepaid_cycle            = s.current_prepaid_cycle,
      s.current_prepaid_cycle            = m.current_prepaid_cycle,
      m.billing_month                    = s.billing_month,
      s.billing_month                    = m.billing_month,
      m.billing_day                      = s.billing_day,
      s.billing_day                      = m.billing_day,
      m.buffer_days                      = s.buffer_days,
      s.buffer_days                      = m.buffer_days,
      m.frequency_id                     = s.frequency_id,
      s.frequency_id                     = m.frequency_id,
      m.is_next_recurring_price_override = s.is_next_recurring_price_override,
      s.is_next_recurring_price_override = m.is_next_recurring_price_override,
      m.original_offer_id                = s.original_offer_id,
      s.original_offer_id                = m.original_offer_id
 WHERE
      m.order_id = ?
   AND
      m.type_id = 1
   AND
      s.order_id = ?
   AND
      s.type_id = 2
SQL;
                DB::update($bmSqlRaw, [
                    $this->id,
                    $swapToMain->id,
                ]);
                DB::update($orderSqlRaw, [
                    $this->recurring_date,
                    $this->subscription_id,
                    $this->id,
                    $swapToMain->id,
                    $this->id,
                    $swapToMain->id,
                    $this->id,
                    $this->id,
                    $swapToMain->id,
                    $swapToMain->id,
                ]);

                DB::commit();

                $swapped = true;
                $this->swappedMainToUpsellId = $swapToMain->id;
            }
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return $swapped;
    }

    /**
     * @return mixed|string
     */
    protected function getShippingOrderIdAttribute()
    {
        $adjustedOrderId = $this->id;
        $shipments       = $this->history_notes()
            ->whereIn('type', ['order-fulfillment-success', 'history-note-order-fulfillment-force-ship'])
            ->get();

        if ($count = $shipments->count()) {
            $adjustedOrderId .= "-{$count}";
        }

        return $adjustedOrderId;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->history_notes()
            ->where('type', OrderHistoryNoteType::TYPE_NOTES)
            ->first()->status;
    }

    /**
     * @return array
     */
    protected function getProductLineItemDetailsAttribute(): array
    {
        $productLineItemDetails = [];

        $this->products
            ->each(function ($product) use (&$productLineItemDetails) {
                if (!$product->is_fully_refunded) {
                    $useChildrenSku = (isset($this->override_use_children_sku)) ? $this->override_use_children_sku : $product->product->use_children_sku;
                    if ($product->product->is_bundle && (empty($product->product_sku) || $useChildrenSku)) {
                        foreach ($product->bundle_products()->get() as $index => $child_product) {
                            if ($product->product->price_type->id === ProductPriceType::FIXED) {
                                try {
                                    $productDiscount = !$index ? ($product->product_discounts / $child_product->quantity) : 0.00;
                                    $price           = !$index ?
                                        ($product->order_product_unit_price->value * 10000/($child_product->quantity * $product->quantity)/10000) :
                                        0.00;
                                } catch (\DivisionByZeroError $e) {
                                    $productDiscount = 0.00;
                                    $price           = 0.00;
                                }

                                $unitOrderPrice  = $price;
                            } else {
                                $productDiscount = !$index ? ($product->product_discounts / $child_product->quantity) : 0.00;
                                $unitOrderPrice  = $child_product->product_price;
                            }

                            $productLineItemDetails[] = [
                                'id'                     => $child_product->product_id,
                                'sku'                    => $child_product->product->sku,
                                'name'                   => $child_product->product->name,
                                'unitPrice'              => $child_product->product->price,
                                'unitOrderPrice'         => $unitOrderPrice,
                                'quantity'               => $child_product->quantity * $product->quantity,
                                'productDiscounts'       => $productDiscount,
                                'weight'                 => $child_product->product->weight,
                                'weight_unit'            => $child_product->product->weight_unit->name,
                                'description'            => $child_product->product->description,
                                'variant_id'             => '',
                                'is_add_on'              => 0,
                                'is_shippable'           => $child_product->product->is_shippable,
                                'calculatedUnitPrice'    => 0, //currently not used keeping arrays consistent
                                'total_unit_price'       => ($unitOrderPrice - $productDiscount) * $child_product->quantity,
                            ];
                        }
                    } else {

                        if (! empty($product->product_discounts)) {
                            $productDiscount = $product->coupon_discount + $product->billing_model_discount->value;

                            // billing model discount is done preemptively for unitOrderPrice for non bundles and productDiscount
                            // is present need to use unitPrice this scenario else it will try to discount it two fold.
                            // also need to avoid use of unit price for bundles. the last condition in the if statement is for custom price
                            // and those values wont be the same if its a custom price and we need to use the else value
                            if ($product->billing_model_discount->value &&
                                ! $product->product->is_bundle          &&
                                ! $product->product->variant_flag       &&
                                $product->product->price == $product->order_product_unit_price->value
                            ) {
                                $unitPrice = $product->product->price;
                            } else {
                                $unitPrice       = $product->order_product_unit_price->value;
                                $isTrialWorkflow = TrialWorkflowLineItem::where('order_id', $product->orders_id)
                                    ->where('order_type_id', $product->is_main ? OrderSubscription::TYPE_MAIN : OrderSubscription::TYPE_UPSELL)
                                    ->exists();

                                // rebill variant scenario. The Product Discount is more because of the billing discount on rebill and is not the "true"
                                // billing model discount so we are removing the excess amount from the discount cause on rebill for variants
                                // $product->order_product_unit_price->value already has the accurate value accounting for billing model discount
                                // rebill subscription discount scenario via contacts page. same concept applies
                                // Trial Workflow does not include BM discount, so don't remove it
                                if ($this->rebillDepth && $product->billing_model_discount->value && ! $product->product->is_bundle && !$isTrialWorkflow)
                                {
                                    $productDiscount -= $product->billing_model_discount->value;
                                }
                            }

                            $calculatedUnitPrice = $unitPrice - $productDiscount;

                        } else {
                            $calculatedUnitPrice = $product->order_product_unit_price->value;
                        }

                        // Do rebill discount
                        //
                        if (! empty($this->rebillDiscount)) {
                            $unitPriceRebillDiscount = ($this->rebillDiscount/100) * $calculatedUnitPrice;
                            $calculatedUnitPrice     -= $unitPriceRebillDiscount;
                        }

                        // Retry Discount
                        if (! empty($this->retry_discount_amt) && $this->retry_discount_pct) {
                            $calculatedUnitPrice -= $this->retry_discount_pct / 100 * $calculatedUnitPrice;
                        }

                        // then apply subscription_credit
                        //
                        if ($product->billing_model_subscription_credit->value) {
                            $calculatedUnitPrice -= $product->billing_model_subscription_credit->value;
                        }


                        $productLineItemDetails[] = [
                            'id'                  => $product->product_id,
                            'sku'                 => $product->product_sku,
                            'name'                => $product->product_name,
                            'unitPrice'           => $product->product->price,
                            'unitOrderPrice'      => $product->order_product_unit_price->value,
                            'productDiscounts'    => $product->product_discounts,
                            'quantity'            => $product->quantity,
                            'weight'              => $product->product->weight,
                            'weight_unit'         => $product->product->weight_unit->name,
                            'description'         => $product->product->description,
                            'variant_id'          => $product->variant_id,
                            'is_add_on'           => $product->is_add_on,
                            'is_shippable'        => $product->product->is_shippable,
                            'calculatedUnitPrice' => $calculatedUnitPrice,
                            // Price for the unit should be before the coupon disocunt applied
                            'total_unit_price'    => $product->custom_price / $product->quantity,
                        ];
                    }
                }
            });

        return $productLineItemDetails;
    }

    /**
     * @return array
     */
    protected function getShippableProductsAttribute(): array
    {
        $shippableProducts = [];

        if ($productLineItemDetails = $this->product_line_item_details) {
            foreach ($productLineItemDetails as $product) {
                if ($product['is_shippable']) {
                    $shippableProducts[] = $product;
                }
            }
        }

        return $shippableProducts;
    }

    /**
     * @return bool
     */
    protected function getIsPaysafeGatewayAttribute()
    {
        return $this->gateway instanceof GatewayProfile
            && $this->gateway->account->is_paysafe;
    }

    /**
     * @return bool
     */
    protected function getIsNmiPaysafeGatewayAttribute()
    {
        return $this->gateway instanceof GatewayProfile
            && $this->gateway->account->is_nmi_paysafe;
    }

    /**
     * @return string|null
     */
    protected function getConsentIdAttribute()
    {
        if ($this->is_nmi_paysafe_gateway) {
            if ($transaction = NMIPaysafe::forOrder($this->id)->first()) {
                return $transaction->consent_id;
            }
        }

        return null;
    }

    /**
     * @return HasMany
     */
    public function notification_history()
    {
        return $this->hasMany(OrderNotificationHistory::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasMany
     */
    public function order_link_upsells()
    {
        return $this->hasMany(OrderLink::class, 'master_order_id', 'orders_id')
            ->where('type_id', OrderLinkType::UPSELL);
    }

    /**
     * @return HasOne
     */
    public function upsell_link_order(): HasOne
    {
        return $this->hasOne(OrderLink::class, 'linked_order_id', 'orders_id')
            ->where('type_id', OrderLinkType::UPSELL);
    }

    /**
     * @return HasMany
     */
    public function combined_similar_address_orders()
    {
        return $this->hasMany(OrderLink::class, 'master_order_id', 'orders_id')
            ->where('type_id', OrderLinkType::COMBINE_ADDRESS);
    }

    /**
     * @throws VoidProhibitedByProviderException
     * @throws VoidZeroException
     * @throws VoidInvalidStateException
     * @throws VoidInvalidProviderException
     */
    protected function checkVoidEligible() : void
    {
        if ($this->refund_type_id == 3) {
            throw new VoidInvalidStateException;
        } elseif ($this->total_revenue <= 0) {
            throw new VoidZeroException;
        } elseif (! $this->gateway) {
            throw new VoidInvalidProviderException();
        } elseif ((new \gatewayConfiguration($this->gateway->account_id, true, $this->payment_method))->cant('void')) {
            throw new VoidProhibitedByProviderException;
        }
    }

    /**
     * @param bool $keepRecurring
     * @return bool
     * @throws \App\Exceptions\Orders\VoidInvalidProviderException
     * @throws \App\Exceptions\Orders\VoidInvalidStateException
     * @throws \App\Exceptions\Orders\VoidProhibitedByProviderException
     * @throws \App\Exceptions\Orders\VoidZeroException
     */
    public function void($keepRecurring = false) : bool
    {
        $this->checkVoidEligible();

        // @todo refactor api_order_void
        require_once(DIR_FS_ADMIN . 'includes/functions/custom_functions.php');
        [$success] = \api_order_void($this->id, get_current_user(), false, $keepRecurring);

        return $success;
    }

    /**
     * Identifies if the order is partially shipped based on the tracking number
     * @return bool
     */
    public function getIsPartiallyShippedAttribute() : bool
    {
        if (!$this->is_split_shipment) {
            return false;
        }

        $mainTrackingNum   = !empty($this->tracking_num);
        $emptyTrackingNum  = null;
        $activeTrackingNum = null;
        $this->additional_products()
            ->where('payment_module_code', 1)
            ->get()
            ->each(function ($upsell) use (&$emptyTrackingNum, &$activeTrackingNum) {
                if (empty($upsell->tracking_num) || $upsell->tracking_num === '0') {
                    $emptyTrackingNum = true;
                } else {
                    $activeTrackingNum = true;
                }
            });

        return (($activeTrackingNum && $emptyTrackingNum) || ($mainTrackingNum && $emptyTrackingNum) || (!$mainTrackingNum && $activeTrackingNum));
    }

    /**
     * @return bool
     */
    public function getIsFullyShippedAttribute() :bool
    {
        $isFullyShipped = true;

        $this->all_order_items->each(function ($lineItem) use (&$isFullyShipped) {
            $isFullyShipped = $isFullyShipped && !empty($lineItem->tracking_num);

            if (!SMC::check(SMC::SPLIT_SHIPMENT) || !$this->is_split_shipment) {
                return false;
            }
        });

        return $isFullyShipped;
    }

    /**
     * @param string $trackingNumber
     * @return array
     */
    public function getProductsFromTrackingNumber(string $trackingNumber): array
    {
        $products = [];

        $this->all_order_items->each(function ($lineItem) use ($trackingNumber, &$products) {
            if ($lineItem->tracking_number === $trackingNumber) {
                $products[] = $lineItem->order_product->product_id;
            }
        });

        return $products;
    }

    /**
     * @param string $subscriptionId
     * @return null
     */
    public function getOrderLineItem(string $subscriptionId)
    {
        $lineItemResult = null;
        $this->all_order_items->each(function ($lineItem) use ($subscriptionId, &$lineItemResult) {
            if ($lineItem->subscription_id === $subscriptionId) {
                $lineItemResult = $lineItem;
                return false;
            }
        });

        return $lineItemResult;
    }

    /**
     * Identifies if the order is partially shipped based on the tracking number
     * @return bool
     */
    public function getIsSplittableAttribute() : bool
    {
        return SMC::check(SMC::SPLIT_SHIPMENT) &&
            in_array($this->status_id,[OrderStatus::STATUS_APPROVED, OrderStatus::STATUS_VOID]) &&
            ($this->getShippableProductCount() >= 2);
    }

    /**
     * @param Builder $query
     * @param string  $fulfillmentNumber
     * @return Builder
     */
    public function scopeForFulfillmentNumber(Builder $query, string $fulfillmentNumber)
    {
        return $query
            ->where('fulfillmentNumber', $fulfillmentNumber)
            ->orWhereHas('fulfillment_number', function (Builder $q) use ($fulfillmentNumber) {
                $q->where('value', $fulfillmentNumber);
            });
    }

    /**
     * @return HasOne
     */
    public function awaiting_retry_date()
    {
        return $this->hasOne(AwaitingRetryDate::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasMany
     */
    public function smart_dunning_retry_date(): HasMany
    {
        return $this->hasMany(SmartDunningRetryDate::class, 'order_id', 'orders_id')
            ->where('subscription_id', $this->subscription_id);
    }

    public function smart_dunning_retry_time_of_day(): HasOne
    {
        return $this->hasOne(SmartDunningRetryTimeOfDay::class, 'order_id', 'orders_id');
    }

    /**
     * Get upsells recurring on the same day.
     * @return Collection|null
     */
    public function getSameDayUpsellsAttribute(): ?Collection
    {
        return $this->additional_products()
            ->recursAtOrBefore(\Illuminate\Support\Carbon::today()->toDateString())
            ->get();
    }

    /**
     * Mark me as a fraudulent order
     *
     * @return bool
     */
    public function markAsFraud(): bool
    {
        return $this->update(['isFraud' => 1]);
    }

    /**
     * @return int|null
     */
    public function getSwappedMainToUpsellId(): ?int {
        return $this->swappedMainToUpsellId;
    }

    /**
     * @return mixed
     */
    public function getDeclineHistoryNoteAttribute()
    {
        return $this->history_notes()->where('type', 'order-process')->latest()->first();
    }

    /**
     * Get the Buy X Get Y coupon ID.
     * @return HasOne
     */
    public function buyXGetYCouponId()
    {
        return $this->hasOne(BuyXGetYCouponId::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasMany
     */
    public function order_customer_types(): HasMany
    {
        return $this->hasMany(OrderCustomerType::class, 'order_id', 'orders_id');
    }

    /*
     * @return bool
     */
    public function getIsRmaReturnedAttribute(): bool {
        return $this->isRMA === self::IS_RMA_RETURNED;
    }

    /*
     * @return bool
     */
    public function getWasCcUpdatedAttribute(): bool
    {
        return $this->charge_c_orig !== $this->charge_c;
    }

    /**
     * @return Currency
     */
    public function getMcCurrencyAttribute() : Currency
    {
        $mcCurrency = Currency::where('code', $this->getOriginal('currency'))->first();
        if ($mcCurrency !== null)
        {
            return $mcCurrency;
        }
        return $this->currency;
    }

    /**
     * @return HasManyThrough
     */
    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Order\Subscription::class,
            OrderItem::class,
            'order_id',
            'id',
            'orders_id',
            'subscription_id'
        )->groupBy('id');
    }

    /**
     * Get the Backorder type.
     *
     * @return HasOne
     */
    public function backorderType(): HasOne
    {
        return $this->hasOne(Backorder::class, 'order_id', 'orders_id');
    }

    /**
     * @return bool
     */
    public function getIsBackorderAttribute(): bool
    {
        return $this->backorderType()->exists();
    }

    /**
     * @return HasManyThrough
     */
    public function subscriptionsWithChild(): HasManyThrough
    {
        return $this->subscriptions()
            ->whereHas('child');
    }

    /**
     * @return bool
     */
    public function hasChildrenSubscriptions(): bool
    {
        return $this->subscriptionsWithChild()
            ->exists();
    }

    /**
     * Returns a collection of children subscriptions related to this order
     * @return Collection
     */
    public function childrenSubscriptions(): Collection
    {
        return $this
            ->subscriptionsWithChild()
            ->get()
            ->pluck('child');
    }

    public function allSubscriptions(): Collection
    {
        return $this->subscriptions->merge($this->childrenSubscriptions());
    }

    /**
     * @return Collection
     */
    public function getActiveRecurringItemsAttribute(): Collection
    {
        return $this->all_order_items->where('is_active_subscription', true);
    }

    /**
     * @return bool
     */
    public function getShouldStoreNotificationSnapshotsAttribute() : bool
    {
        $shouldStoreSnapshots = ConsentRequired::requiredForOrder($this->ancestor_or_self)->exists();
        $isOrWasRecurring = $this->is_recurring || self::where('orders_id', $this->orders_id)->where('recurring_date', '!=', '0000-00-00')->first();

        if (!$shouldStoreSnapshots) {
            $shouldStoreSnapshots = $isOrWasRecurring && $this->is_paysafe_gateway && $this->is_snapshot_required_cc;
        }
        return $shouldStoreSnapshots;
    }

    /**
     * @return bool
     */
    public function getIsSnapshotRequiredCcAttribute() : bool
    {
        return in_array($this->cc_type, self::SNAPSHOT_REQUIRED_CC_TYPES, true);
    }

    /**
     * @return HasOne
     */
    public function tax_transaction_number(): HasOne
    {
        return $this->hasOne(TaxTransactionNumber::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function line_item_sequence(): HasOne
    {
        return $this->hasOne(LineItemSequence::class, 'order_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function line_item_tax()
    {
        return $this->hasOne(OrderLineItems\LineItemTax::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function line_item_tax_amount()
    {
        return $this->hasOne(OrderLineItems\LineItemTaxAmount::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function shipping_tax_percent()
    {
        return $this->hasOne(OrderLineItems\ShippingTax::class, 'orders_id', 'orders_id');
    }

    /**
     * @return HasOne
     */
    public function shipping_tax_amount()
    {
        return $this->hasOne(OrderLineItems\ShippingTaxAmount::class, 'orders_id', 'orders_id');
    }

    /*
     * Get the announcement status.
     *
     * @return HasOne
     */
    public function announcement(): HasOne
    {
        return $this->hasOne(Announcement::class, 'order_id', 'orders_id');
    }

    public function shouldExcludeBillingModelDiscount(): HasOne
    {
        return $this->hasOne(\App\Models\OrderAttributes\ShouldExcludeBillingModelDiscount::class, 'order_id', 'orders_id');
    }

    /*
     * Get the next line item volume discount
     *
     * @return HasOne
     */
    public function nextLineItemVolumeDiscount(): HasOne
    {
        return $this->hasOne(OrderProductVolumeDiscountPrice::class, 'orders_id', 'orders_id');
    }

    /* Get the Reshipment Count.
     *
     * @return HasOne
     */
    public function reshipmentCount(): HasOne
    {
        return $this->hasOne(ReshipmentCount::class, 'order_id', 'orders_id');
    }

    /**
     * Check if order has at least one active recurring subscription
     *
     * @param $orderId
     * @return bool
     */
    public static function hasActiveRecurringItems($orderId): bool
    {
        $upsellOrders = Upsell::select('main_orders_id AS orders_id')
            ->where([
                'main_orders_id' => $orderId,
                'is_recurring'   => 1,
                'is_hold'        => 0,
                'is_archived'    => 0,
                'deleted'        => 0,
            ]);

        return self::readOnly()
            ->customSelectWithComment('orders_id', 'Check if order has at least one active recurring subscription', __FILE__, __METHOD__)
            ->where([
                'orders_id'    => $orderId,
                'is_recurring' => 1,
                'is_hold'      => 0,
                'is_archived'  => 0,
            ])
            ->union($upsellOrders)
            ->exists();
    }

    /**
     * Using slave connection this function is checking if this Order has at least one Collection Offer Type Subscription
     *
     * @param $orderId
     * @return bool
     */
    public static function isCollectionOrder($orderId): bool
    {
        // Using billing_model_order table check if this main order has offer with collection offer type
        $isCollectionOrder = OrderSubscription::readOnly()
            ->selectWithComment('Check if MAIN order has at least one collection subscription', __FILE__, __METHOD__)
            ->forOrder($orderId)
            ->hasByNonDependentSubquery('offer', fn($q) => $q->isCollectionOffer())
            ->exists();

        if (! $isCollectionOrder) {
            // Check if this order has at least one upsell with billing model that has offer with collection offer type
            $isCollectionOrder = Upsell::readOnly()
                ->selectWithComment('Check if UPSELL order has at least one collection subscription', __FILE__, __METHOD__)
                ->where('main_orders_id', $orderId)
                ->hasByNonDependentSubquery('order_subscription', function ($q) {
                    $q->hasByNonDependentSubquery('offer', fn($q) => $q->isCollectionOffer());
                })
                ->exists();
        }

        return $isCollectionOrder;
    }
}
