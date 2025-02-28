<?php

namespace App\Models;

use App\Models\EntityAttributes\UpsellAttributes\AwaitingRetryDate;
use App\Models\OrderAttributes\Announcement;
use App\Models\SmartDunning\SmartDunningRetryDate;
use App\Models\SmartDunning\SmartDunningRetryTimeOfDay;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Credits\Subscription as SubscriptionCredit;
use App\Models\OrderLineItems\UpsellSubTotal;
use App\Models\OrderLineItems\UpsellTotal;
use App\Traits\HasSubscriptionPieces;
use App\Models\BillingModel\OrderSubscription;
use App\Models\Campaign\Campaign;
use App\Models\Contact\Contact;

/**
 * Class Upsell
 *
 * @package App\Models
 *
 * @property mixed $id
 * @property mixed $campaign_id
 * @property mixed $cc_type
 * @property mixed $first_six
 * @property mixed $subscription_id
 *
 * @method static Builder|Upsell where($column, $operator = null, $value = null, $boolean = 'and')
 */
class Upsell extends Subscription
{
    use HasSubscriptionPieces;

    const CREATED_AT = 't_stamp';
    const UPDATED_AT = 'last_modified';

    /**
     * @var string
     */
    protected $table = 'upsell_orders';

    /**
     * @var string
     */
    protected $primaryKey = 'upsell_orders_id';

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'parent_id',
        'campaign_id',
        'customer_id',
        'prospect_id',
        'return_reason_id',
        'unbundled_child_id',
        'is_deleted',
        'is_shippable',
        'is_shipped',
        'created_at',
        'hold_at',
        'recur_at',
        'recurring_date',
        'retry_at',
        'shipped_at',
        'returned_at',
        'ip_address',
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'state_id',
        'zip',
        'country_id',
        'bill_first_name',
        'bill_last_name',
        'bill_address',
        'bill_address2',
        'bill_city',
        'bill_zip',
        'bill_state',
        'bill_state_id',
        'bill_country_id',
        'status_id',
        'payment_method',
        'cc_expiry',
        'forecasted_revenue',
        'currency_value',
        'is_recurring',
        'is_hold',
        'custom_rec_prod_id',
        'tracking_number',
        'is_archived',
        'hold_date',
    ];

    /**
     * @var array
     */
    protected $dates = [
        't_stamp',
        'recurring_date',
        'date_purchased',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'campaign_id',
        'customer_id',
        'billing',
        'shipping',
        // Dates
        'created_at',
        'recur_at',
        // Customer
        'email',
        'phone',
        // Misc
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'campaign_id',
        'customer_id',
        // Customer
        'email',
        'phone',
        // Dates
        'created_at',
        'recur_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
       'id'                         => 'upsell_orders_id',
       // IDs
       'order_id'                   => 'main_orders_id',
       'parent_id'                  => 'parent_order_id',
       'campaign_id'                => 'campaign_order_id',
       'customer_id'                => 'customers_id',
       'prospect_id'                => 'prospects_id',
       'return_reason_id'           => 'paypal_ipn_id',
       'unbundled_child_id'         => 'child_order_id',
       // Flags
       'is_deleted'                 => 'deleted',
       'is_shippable'               => 'payment_module_code',
       'is_shipped'                 => 'shipping_module_code',
       // Dates
       'created_at'                 => 't_stamp',
       'hold_at'                    => 'hold_date',
       'recur_at'                   => 'recurring_date',
       'retry_at'                   => 'date_purchased',
       'shipped_at'                 => 'orders_date_finished',
       'returned_at'                => 'last_modified',
       // Customer
       'email'                      => 'customers_email_address',
       'phone'                      => 'customers_telephone',
       'first_name'                 => 'delivery_fname',
       'last_name'                  => 'delivery_lname',
       'address'                    => 'delivery_street_address',
       'address2'                   => 'delivery_suburb',
       'city'                       => 'delivery_city',
       'state'                      => 'delivery_state',
       'state_id'                   => 'delivery_state_id',
       'zip'                        => 'delivery_postcode',
       'country_id'                 => 'delivery_country',
       'bill_first_name'            => 'billing_fname',
       'bill_last_name'             => 'billing_lname',
       'bill_address'               => 'billing_street_address',
       'bill_address2'              => 'billing_suburb',
       'bill_city'                  => 'billing_city',
       'bill_zip'                   => 'billing_postcode',
       'bill_state'                 => 'billing_state',
       'bill_state_id'              => 'billing_state_id',
       'bill_country_id'            => 'billing_country',
       // Misc
       'status_id'                  => 'orders_status',
       'payment_method'             => 'cc_type',
       'cc_expiry'                  => 'cc_expires',
       'forecasted_revenue'         => 'currency_value',
       'custom_rec_prod_id'         => 'recurring_product_custom',
       'is_add_on'                  => 'add_on_flag',
       'tracking_number'            => 'tracking_num',
       'stopRecurringOnNextSuccess' => 'stop_recurring_on_next_success',
    ];

    // Defaults
    protected $attributes = [
        'parent_order_id' => 0,
    ];

    /**
     * @var Contact|null
     */
    protected ?Contact $contact = null;

    public static function boot()
    {
        parent::boot();

        static::creating(function (self $upsell) {
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
                if (! strlen($upsell->$prop)) {
                    $upsell->setAttribute($prop, $upsell->getAttribute($map_to));
                }
            }
        });

        static::deleting(function (self $upsell) {
            $upsell->order_product()->delete();
        });
    }

    /**
     * @param Order $order
     * @param       $productData
     * @return mixed
     */
    public static function createFromOrder(Order $order, $productData)
    {
        $skip = [
            'order_id',
            'parent_id',
            'prospect_id',
            'return_reason_id',
            'unbundled_child_id',
        ];

        $upsell = static::make([
            'order_id'   => $order->getAttribute('id'),
            'is_deleted' => 0,
        ]);

        foreach ($upsell->getFillable() as $prop) {
            if (! in_array($prop, $skip)) {
                $upsell->setAttribute($prop, $order->getAttribute($prop));
            }
        }

        $upsell->save();

        $productData['upsell_orders_id'] = $upsell->getAttribute('id');

        UpsellProduct::create($productData);

        return $upsell;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function campaign()
    {
        return $this->hasOne(Campaign::class, 'c_id', 'campaign_order_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'customers_id', 'customers_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function main()
    {
        return $this->hasOne(Order::class, 'orders_id', 'main_orders_id');
    }

    /**
     * @param        $query
     * @param string $recurAt
     * @return mixed
     */
    public function scopeRecursAtOrBefore($query, string $recurAt)
    {
        return $query->where('recur_at', '<=', $recurAt);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order_product()
    {
        return $this->hasOne(UpsellProduct::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return int
     */
    public function getProductIdAttribute(): int
    {
        if ($orderProduct = $this->order_product()->first()) {
            return $orderProduct->products_id;
        }

        return 0;
    }

   /**
    * @return HasMany
    */
   public function history_notes()
   {
      return $this->hasMany(OrderHistoryNote::class, 'orders_id', 'main_orders_id');
   }

    /**
     * @return int
     */
    public function getTypeIdAttribute()
    {
        return OrderSubscription::TYPE_UPSELL;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function total()
    {
        return $this->hasOne(UpsellTotal::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subtotal()
    {
        return $this->hasOne(UpsellSubTotal::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription_override()
    {
        return $this->hasOne(SubscriptionOverride::class, 'subscription_id', 'subscription_id')
            ->whereNull('consumed_at');
    }

    /**
     * @return array
     */
    public function getNextShippingAddressAttribute()
    {
        if ($this->subscription_override && $this->subscription_override->address) {
            return $this->subscription_override->address->toArray();
        }

        return (array) $this->main->shipping;
    }

    /**
     * @return array
     */
    public function getNextBillingAddressAttribute()
    {
        if ($this->subscription_override && $this->subscription_override->contact_payment_source) {
            return $this->subscription_override->contact_payment_source->address->toArray();
        }

        return (array) $this->main->billing;
    }

    /**
     * @return mixed
     */
    public function getMainOrderIdAttribute()
    {
        return $this->getAttribute('main_orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription_credit()
    {
        return $this->hasOne(SubscriptionCredit::class, 'item_id', 'subscription_id');
    }

    /**
     * @return bool
     */
    public function getIsActiveSubscriptionAttribute()
    {
        return ($this->is_recurring && ! $this->is_hold && ! $this->is_archived);
    }

    /**
     * @return Carbon
     */
    public function getAttemptAtAttribute()
    {
        return Carbon::parse($this->retry_at->year > 0 ? $this->retry_at : $this->recur_at);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function declined_cc()
    {
        return $this->hasOne(DeclinedCC::class, 'orders_id', 'upsell_orders_id')
            ->where('is_order_or_upsell', 1);
    }

    /**
     * @return int
     */
    public function getRetryAttemptAttribute()
    {
        return $this->declined_cc ? $this->declined_cc->attempt_no : 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cascade_force()
    {
        return $this->hasOne(CascadeForce::class, 'orders_id', 'main_orders_id');
    }

    /**
     * @return mixed
     */
    public function getGatewayIdAttribute()
    {
        return $this->main->gateway_id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function prepaid_discount()
    {
        return $this->hasOne(OrderLineItems\UpsellPrepaidDiscount::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return bool
     */
    public function isShippable()
    {
        return (($this->is_shippable != '') ? $this->is_shippable : $this->order_product->product->is_shippable);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function awaiting_retry_date()
    {
        return $this->hasOne(AwaitingRetryDate::class, 'entity_primary_id', 'upsell_orders_id');
    }

    /**
     * @return HasMany
     */
    public function smart_dunning_retry_date(): HasMany
    {
        return $this->hasMany(SmartDunningRetryDate::class, 'order_id', 'main_orders_id')
            ->where('subscription_id', $this->subscription_id);
    }

    /**
     * @return HasOne
     */
    public function smart_dunning_retry_time_of_day(): HasOne
    {
        return $this->hasOne(SmartDunningRetryTimeOfDay::class, 'order_id', 'main_orders_id');
    }

    /**
     * @return int
     */
    public function getRebillDepthAttribute(): int
    {
        return $this->main()->first()->rebillDepth;
    }

    /**
     * @return HasOne
     */
    public function order_subscription(): HasOne
    {
        return $this->hasOne(OrderSubscription::class, 'order_id', 'upsell_orders_id')
            ->where('type_id', OrderSubscription::TYPE_UPSELL);
    }

    /**
     * @return HasOne|null
     */
    public function contact(): ?HasOne
    {
        return $this->hasOne(Contact::class, 'email', 'customers_email_address');
    }

    /**
     * @return Contact|null
     */
    public function getContactAttribute(): ?Contact
    {
        if (!$this->contact) {
            $this->contact = Contact::firstOrCreate([
                'email'      => $this->getAttribute('email'),
            ], [
                'first_name' => $this->getAttribute('first_name'),
                'last_name'  => $this->getAttribute('last_name'),
                'phone'      => $this->getAttribute('phone'),
            ]);
        }

        return $this->contact;
    }

    /**
     * @return mixed
     */
    public function getAncestorIdAttribute()
    {
        return $this->main->ancestor_id;
    }


    /**
     * @param $tokenKey
     * @return null
     */
    public function getCustomFieldValues($tokenKey = null)
    {
        // upsells cannot have custom field values
        return null;
    }

   /**
    * @return string
    */
   protected function getStopRecurringOnNextSuccessAttribute()
   {
      return $this->main->stopRecurringOnNextSuccess;
   }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function line_item_tax()
    {
        return $this->hasOne(OrderLineItems\UpsellLineItemTax::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return HasOne
     */
    public function line_item_tax_amount()
    {
        return $this->hasOne(OrderLineItems\UpsellLineItemTaxAmount::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * Get the announcement status.
     *
     * @return HasOne
     */
    public function announcement(): HasOne
    {
        return $this->hasOne(Announcement::class, 'order_id', 'main_orders_id');
    }

    /*
     * Get the next line item volume discount
     *
     * @return HasOne
     */
    public function nextLineItemVolumeDiscount(): HasOne
    {
        return $this->hasOne(UpsellProductVolumeDiscountPrice::class, 'upsell_orders_id', 'upsell_orders_id');
    }

    /**
     * @return HasOne
     */
    public function rebill_discount_amount(): HasOne
    {
        return $this->hasOne(OrderLineItems\UpsellRebillDiscount::class, 'upsell_orders_id', 'upsell_orders_id');
    }
}
