<?php

namespace App\Models;

use App\Models\OrderLineItems\LineItemCustomOption;
use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Offer\Offer;
use App\Models\BillingModel\BillingModel;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\App;
use App\Models\Contact\Contact;

/**
 * Class LegacySubscription
 *
 * Reader for the v_subscriptions view, uses slave connection.
 *
 * @package App\Models
 */
class LegacySubscription extends Model
{
    use ModelImmutable;

    const UPDATED_AT       = null;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_RETRYING  = 'retrying';
    public const STATUS_CANCELLED = 'cancelled';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    protected $table = 'v_subscriptions';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected static $visibleForApi = [
        'id',
        'order_id',
        'contact_id',
        'email',
        'phone',
        'is_recurring',
        'billing_model',
        'depth',
        'recur_at',
        'created_at',
        'credit',
        'credit_card',
        'forecasted_revenue',
        'product',
        'next_product',
        'shipping',
        'discounts',
        'status',
        'is_system_hold',
        'hold_type_name',
        'decline_reason',
        'next_variant_id',
        'next_recurring_details',
        'custom_fields',
        'options',
        'stop_after_next_rebill',
    ];

    /**
     * @var array
     */
    protected static $appendedForApi = [
        'contact_id',
        'phone',
        'billing_model',
        'product',
        'credit_card',
        'next_product',
        'shipping',
        'discounts',
        'status',
        'decline_reason',
        'next_recurring_details',
        'custom_fields',
        'options',
        'stop_after_next_rebill',
    ];

    /**
     * @return array
     */
    public static function getVisibleForApi()
    {
        return self::$visibleForApi;
    }

    /**
     * @return array
     */
    public static function getAppendsForApi()
    {
        return self::$appendedForApi;
    }

    /**
     * @return array
     */
    protected function getProductAttribute()
    {
        $data = [
            'id'            => $this->product_id,
            'category'      => [],
            'variant_id'    => $this->variant_id,
            'name'          => $this->product_name,
            'quantity'      => $this->quantity,
            'sku'           => '',
            'is_shippable'  => '',
            'children'      => [],
            'custom_fields' => [],
        ];

        if ($this->bundle_products()->count()) {
            $orderProductBundles = $this->bundle_products()->get();

            foreach ($orderProductBundles as $orderProductBundle) {
                $data['children'][] = [
                    'product_id' => $orderProductBundle->product_id,
                    'quantity'   => $orderProductBundle->quantity,
                    'price'      => $orderProductBundle->charged_price,
                ];
            }
        }

        if ($productModel = $this->product()->first()) {
            $data['category']      = $productModel->category;
            $data['sku']           = $productModel->sku;
            $data['is_shippable']  = $productModel->is_shippable;
            $data['custom_fields'] = $productModel->custom_fields;
        }

        if ($this->variant_id && $productVariant = $this->variant()->first()) {
            $data['variant'] = $productVariant;
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getNextProductAttribute()
    {
        $data = [
            'id'            => $this->next_product_id,
            'category'      => [],
            'name'          => $this->next_product_name,
            'quantity'      => $this->next_quantity,
            'variant'       => $this->next_variant_id,
            'sku'           => '',
            'price'         => $this->product()->first()->price,
            'is_shippable'  => '',
            'children'      => [],
            'custom_fields' => [],
        ];

        if ($this->next_bundle_products()->count()) {
            $orderProductBundles = $this->next_bundle_products()->get();

            foreach ($orderProductBundles as $orderProductBundle) {
                $data['children'][] = [
                    'product_id' => $orderProductBundle->product_id,
                    'quantity'   => $orderProductBundle->quantity,
                    'price'      => $orderProductBundle->product_price,
                ];
            }
        }

        if ($productModel = Product::find($this->next_product_id)) {
            $data['category']      = $productModel->category;
            $data['sku']           = $productModel->sku;
            $data['is_shippable']  = $productModel->is_shippable;
            $data['custom_fields'] = $productModel->custom_fields;
        }

        if ($this->next_variant_id && $productVariant = $this->next_variant()->first()) {
            $data['variant'] = $productVariant;
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getShippingAttribute()
    {
        return [
            'first_name'   => $this->ship_first_name,
            'last_name'    => $this->ship_last_name,
            'address'      => $this->ship_address,
            'address2'     => $this->ship_address2,
            'city'         => $this->ship_city,
            'state'        => $this->ship_state,
            'zip'          => $this->ship_zip,
            'country'      => $this->ship_country,
            'country_iso2' => $this->ship_country_iso2,
        ];
    }

    /**
     * @return array
     */
    protected function getDiscountsAttribute()
    {
        return [
            'flat_amount' => $this->discount_flat_amount,
            'percent'     => $this->discount_percent,
            'prepaid'     => $this->prepaid_discount,
        ];
    }
    /**
     * @return int
     */
    protected function getContactIdAttribute()
    {
        $contact = $this->contact;
        return $contact->id ?? null;
    }

    /**
     * @return string
     */
    protected function getStatusAttribute()
    {
        $status = '';

        switch (true) {
            case $this->is_recurring == 1:
                if (!empty($this->retry_at)) {
                    $status = self::STATUS_RETRYING;
                } else {
                    $status = self::STATUS_ACTIVE;
                }
                break;
            case $this->hold_type_id == SubscriptionHoldType::MERCHANT:
                $status = self::STATUS_PAUSED;
                break;
            default:
                $status = self::STATUS_CANCELLED;
                break;
        }

        return $status;
    }

    /**
     * @return string
     */
    public function getDeclineReasonAttribute()
    {
        $decline_reason = '';

        if ($this->isRetrying() || ($this->isCancelled() && $this->heldByDeclineSalvage())) {
            if ($lastDecline = $this->order->last_decline) {
                if ($latestDeclineNote = $lastDecline->decline_history_note) {
                    return $latestDeclineNote->message;
                }
            }
        }

        return $decline_reason;
    }

    /**
     * Get the credit card node.
     *
     * @return array
     */
    protected function getCreditCardAttribute(): array
    {
        return [
            'type'     => $this->payment_method,
            'last_4'   => $this->cc_last_4,
            'first_6'  => $this->cc_first_6,
            'exp_date' => $this->cc_expiry,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'products_id', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function billing_model()
    {
        return $this->belongsTo(BillingModel::class, 'billing_model_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'orders_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sub_type()
    {
        return $this->belongsTo(SubscriptionType::class, 'sub_type_id');
    }

    /**
     * @return array
     */
    public function getOrderIdsAttribute()
    {
        $related = [$this->order_id];

        if ($this->ancestor_id) {
            // In-chain
            $related = Order::where('ancestor_id', $this->ancestor_id)->orWhere('id', $this->ancestor_id)->get()->pluck('order_id')->toArray();
        } else {
            // Initial
            $related = array_merge($related, Order::where('ancestor_id', $this->order_id)->get()->pluck('order_id')->toArray());
        }

        return $related;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bundle_products()
    {
        return $this->hasMany(OrderProductBundle::class, 'order_id', 'order_id')->where('bundle_id', $this->product_id)->where('is_next_cycle', 0)->where('is_main', $this->is_main);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function next_bundle_products()
    {
        return $this->hasMany(OrderProductBundle::class, 'order_id', 'order_id')->where('bundle_id', $this->next_product_id)->where('is_next_cycle', 1)->where('is_main', $this->is_main);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function next_variant()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'next_variant_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variant()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'variant_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription_override()
    {
        return $this->hasOne(SubscriptionOverride::class, 'subscription_id', 'id')->whereNull('consumed_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription_options()
    {
        return $this->hasMany(LineItemCustomOption::class, 'subscription_id', 'id');
    }

    /**
     * @return array
     */
    protected function getBillingModelAttribute()
    {
        return $this->billing_model()->first();
    }

    /**
     * @return array
     */
    protected function getCustomFieldsAttribute()
    {
        return $this->order->custom_fields;
    }

    /**
     * @return array
     */
    protected function getNextShippingAddressAttribute()
    {
        $override = $this->subscription_override;

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

        return $this->shipping;
    }

    /**
     * @return array
     */
    protected function getNextBillingAddressAttribute()
    {
        $override = $this->subscription_override;

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
            $mainOrderModel = $this->order;

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
     * @return array
     */
    public function getNextRecurringDetailsAttribute()
    {
        $response = [
            'product'          => $this->next_product,
            'shipping_method'  => $this->order->shipping_method,
            'shipping'         => $this->next_shipping_address,
            'payment_source'   => 'N/A',
            'billing'          => $this->next_billing_address,
            'billing_model_id' => $this->billing_model_id,
            'recurring_date'   => $this->retry_at ?? $this->recur_at,
        ];

        $override = $this->subscription_override;

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

    /**
     * @return array
     */
    protected function getOptionsAttribute()
    {
        $options = $this->subscription_options;

        if ($options) {
            $result = [];

            $options->each(function ($option) use (&$result) {
                $result [] = [
                    'id'    => $option->id,
                    'name'  => $option->name,
                    'value' => $option->value,
                ];
            });
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getPhoneAttribute()
    {
        $mainOrder = $this->order;

        if ($this->is_main) {
            return $mainOrder->phone;
        } else {
            if ($upsell = $mainOrder->additional_products()->where('subscription_id', $this->id)->first()) {
                return $upsell->phone;
            } else {
                return '';
            }
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $user = App::getFacadeApplication()->get('request')->user();

        $this->setAppends(self::getAppendsForApi());

        if ($user instanceof ApiUser) {
            $this->setVisible(self::getVisibleForApi());
        }

        return parent::toArray();
    }

    /**
     *
     * @return bool
     */
    protected function isRetrying(): bool
    {
        return $this->status === self::STATUS_RETRYING;
    }

    /**
     *
     * @return bool
     */
    protected function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     *
     * @return bool
     */
    protected function heldByDeclineSalvage(): bool
    {
        return $this->hold_type_id == SubscriptionHoldType::DECLINE_SALVAGE;
    }

    /**
     * @return string
     */
    protected function getStopAfterNextRebillAttribute()
    {
        return $this->order->stopRecurringOnNextSuccess;
    }

}
