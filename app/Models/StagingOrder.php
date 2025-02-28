<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderAttributes\OriginTypeId;

class StagingOrder extends Model
{
    protected $hidden = [
        'batch_id',
    ];

    protected $appends = [
        'batch_key',
    ];

    protected $guarded = [
        'id',
    ];

    private $parent_id;
    private $ancestor_id;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo(StagingBatch::class, 'batch_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(StagingOrderProduct::class, 'staging_order_id', 'id');
    }

    public function getBatchKeyAttribute()
    {
        return $this->batch()->first()->id_key;
    }

    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;
    }

    public function setAncestorId($ancestor_id)
    {
        $this->ancestor_id = $ancestor_id;
    }

    public static function createWithProducts($data, $products)
    {
        $order = self::create($data);

        foreach ($products as $product) {
            $product['staging_order_id'] = $order->id;

            StagingOrderProduct::create($product);
        }

        return $order;
    }

    public function promoteToOrder()
    {
        // Already processed, possibly as part of a subscription
        if ($this->promoted_to) {
            return false;
        }

        $order_data = [
             'created_at'         => $this->acquired_at,
             'customer_id'        => null, // Will be populated by Order model
             'campaign_id'        => $this->campaign_id,
             'email'              => $this->email,
             'phone'              => $this->phone,
             'ip_address'         => $this->ip_address,
             'first_name'         => $this->ship_first_name,
             'last_name'          => $this->ship_last_name,
             'address'            => $this->ship_address_1,
             'address2'           => $this->ship_address_2,
             'city'               => $this->ship_city,
             'state'              => $this->ship_state,
             'state_id'           => $this->ship_state, // @todo id
             'zip'                => $this->ship_zip,
             'country_id'         => Country::where('iso_2', $this->ship_country)->first()->id, // $this->ship_country @todo id
             'bill_first_name'    => $this->bill_first_name,
             'bill_last_name'     => $this->bill_last_name,
             'bill_address'       => $this->bill_address_1,
             'bill_address2'      => $this->bill_address_2,
             'bill_city'          => $this->bill_city,
             'bill_zip'           => $this->bill_zip,
             'bill_state'         => $this->bill_state,
             'bill_state_id'      => $this->bill_state, // @todo id
             'bill_country_id'    => Country::where('iso_2', $this->bill_country)->first()->id, // $this->bill_country @todo id
             'is_shippable'       => $this->is_shippable,
             'is_shipped'         => $this->is_shipped,
             'shipping_method'    => Shipping::find($this->shipping_method_id)->name,
             'tracking_num'       => $this->tracking_number,
             'fulfillment_number' => $this->fulfillment_number,
             'shipped_at'         => $this->shipped_at,
             'status_id'          => $this->determineStatusId(),
             'transaction_id'     => $this->transaction_id,
             'auth_id'            => $this->auth_id,
             'is_chargeback'      => $this->is_chargeback,
             'is_fraud'           => $this->is_fraud,
             'notes'              => $this->notes,
        ];

        if (isset($this->parent_id)) {
            $order_data['parent_id'] = $this->parent_id;
        }

        if (isset($this->ancestor_id)) {
            $order_data['ancestor_id'] = $this->ancestor_id;
        }

        if (strlen($this->cc_encrypted)) {
            $order_data = array_merge(
                 $order_data,
                 [
                     'cc_type'      => $this->cc_type,
                     'cc_encrypted' => $this->cc_encrypted,
                     'cc_first_6'   => $this->cc_first_six,
                     'cc_last_4'    => $this->cc_last_four,
                     'cc_length'    => $this->cc_length,
                     'cc_expiry'    => $this->cc_expiry_month . $this->cc_expiry_year,
                     'cvv_length'   => 3, // We won't know length, CVV is not included with historic imports
                 ]
             );
        }

        foreach ($this->products as $i => $staged_product) {
            $product_data[$i] = [
                 'products_id'             => $staged_product->product_id,
                 'products_name'           => $this->getProductName($staged_product->product_id),
                 'products_quantity'       => $staged_product->quantity,
                 'total'                   => $staged_product->charged_amount,
                 'subtotal'                => $staged_product->subtotal_amount,
                 'tax_pct'                 => $staged_product->tax_pct,
                 'tax_amount'              => $staged_product->tax_amount,
                 'taxable_amount'          => $staged_product->taxable_amount,
                 'non_taxable_amount'      => $staged_product->non_taxable_amount,
                 'restocking_fee'          => $staged_product->restocking_fee,
                 'offer_id'                => $staged_product->offer_id,
                 'billing_model_id'        => $staged_product->billing_model_id,
                 'is_trial'                => $staged_product->is_trial,
                 'step_num'                => $staged_product->step_number,
                 'is_recurring'            => 0,
                 'is_archived'             => 0,
                 'is_hold'                 => 0,
                 'recurring_date'          => '0000-00-00',
                 'is_prepaid'              => 0,
                 'prepaid_cycles'          => 0,
                 'current_prepaid_cycle'   => 0,
                 'is_prepaid_subscription' => 0,
                 'cycles_remaining'        => 0,
                 'cycle_depth'             => 0,
                 'refund_total'            => 0, // @todo
                 'return_quantity'         => 0, // @todo
                 'return_reason_id'        => 0, // @todo
            ];

            if ($staged_product->subscription_status) {
                $product_data[$i]['rebill_depth'] = $staged_product->subscription_cycle_depth;
                $product_data[$i]['recurring_date'] = $staged_product->subscription_attempt_at;

                switch ($staged_product->subscription_status) {
                     case 'billed':
                        $product_data[$i]['is_archived'] = 1;
                     break;
                     case 'hold':
                        $product_data[$i]['is_hold'] = 1;
                 }
            }

            if ($staged_product->prepaid_term) {
                $product_data[$i]['is_prepaid'] = 1;
                $product_data[$i]['prepaid_cycles'] = $staged_product->prepaid_term;
                $product_data[$i]['current_prepaid_cycle'] = $staged_product->prepaid_cycle;
                $product_data[$i]['is_prepaid_subscription'] = $staged_product->is_prepaid_hold ? 0 : 1; // @todo lookup sub?
            }
        }

        $order    = $this->createOrderWithProducts($order_data, $product_data);
        $order_id = $order->getAttribute('id');
        $author   = \get_current_user_id();

        OrderHistoryNote::create([
            'order_id'    => $order_id,
            'user_id'     => $author,
            'type'        => 'created-by-historic',
            'status'      => $this->batch->id_key,
            'campaign_id' => $order->getAttribute('campaign_id'),
        ]);

        if (strlen($order->notes)) {
            OrderHistoryNote::create([
                'order_id'    => $order_id,
                'user_id'     => $author,
                'type'        => 'notes',
                'status'      => $order->notes,
                'campaign_id' => $order->getAttribute('campaign_id'),
                'created_at'  => $order->getAttribute('created_at'),
            ]);
        }

        OriginTypeId::createForOrder($order_id, OriginTypeId::TYPE_HISTORIC);

        $this->batch->trackIncrement('order_count', 1);
        $this->batch->trackIncrement('revenue_total', $order->totalRevenue);

        $this->promoted_to = $order_id;
        $this->save();

        return $order;
    }

    private function createOrderWithProducts($order_data, $products)
    {
        $order        = null;
        $order_id     = 0;
        $currency_id  = 1;
        $subscription = null;
        $author       = \get_current_user_id();

        $copy_from_product = [
            'is_recurring',
            'is_archived',
            'is_hold',
            'recurring_date',
            'rebill_depth',
        ];

        $copy_to_subscription = [
            'billing_model_id',
            'is_trial',
            'is_prepaid',
            'prepaid_cycles',
            'current_prepaid_cycle',
            'is_prepaid_subscription',
            'created_by',
            'cycles_remaining',
            'cycle_depth',
        ];

        $aggregate_totals = [
            'total'              => OrderLineItems\Total::make(),
            'tax_amount'         => OrderLineItems\TaxTotal::make(),
            'taxable_amount'     => OrderLineItems\TaxableTotal::make(),
            'non_taxable_amount' => OrderLineItems\NonTaxableTotal::make(),
            'restocking_fee'     => OrderLineItems\RestockingFee::make(),
        ];

        $singular_totals = [
            'subtotal'        => OrderLineItems\SubTotal::make(),
            'shipping_amount' => OrderLineItems\ShippingTotal::make(),
            'tax_pct'         => OrderLineItems\TaxPct::make(),
        ];

        $omit_if_zero = [
            'restocking_fee',
        ];

        if (isset($order_data['currency_id'])) {
            $currency_id = $order_data['currency_id'];
            unset($order_data['currency_id']);
        }

        foreach ($products as $i => $product_data) {
            $subscription_data = [];
            $upsell_totals = [];

            if ($i > 0) {
                $upsell_totals = [
                    'total'           => OrderLineItems\UpsellTotal::make(),
                    'subtotal'        => OrderLineItems\UpsellSubTotal::make(),
                    'shipping_amount' => OrderLineItems\UpsellShippingTotal::make(),
                ];
            }

            foreach ($copy_from_product as $key) {
                $order_data[$key] = $product_data[$key];
                unset($product_data[$key]);
            }

            foreach ($copy_to_subscription as $key) {
                if (array_key_exists($key, $product_data)) {
                    $subscription_data[$key] = $product_data[$key];
                    unset($product_data[$key]);
                }
            }

            foreach ($aggregate_totals as $key => $model) {
                if (isset($product_data[$key])) {
                    // Some totals are omitted when zero
                    if ($product_data[$key] == 0 && in_array($key, $omit_if_zero)) {
                        $aggregate_totals[$key] = null;
                    } else {
                        $model->add($product_data[$key]);
                    }

                    if (isset($upsell_totals) && array_key_exists($key, $upsell_totals)) {
                        $upsell_totals[$key]->setAttribute('value', $product_data[$key]);
                    }

                    unset($product_data[$key]);
                }
            }

            foreach ($singular_totals as $key => $model) {
                if (isset($product_data[$key])) {
                    if (! $i) {
                        $model->add($product_data[$key]);
                    } else if (isset($upsell_totals) && array_key_exists($key, $upsell_totals)) {
                        $upsell_totals[$key]->setAttribute('value', $product_data[$key]);
                    }

                    unset($product_data[$key]);
                }
            }

            // First product is the "main"
            if (! $i) {
                $order    = Order::create($order_data);
                $order_id = $order->getAttribute('id');

                $product_data['orders_id'] = $order_id;

                OrderProduct::create($product_data);

                // Subscription
                $subscription = Subscription::create([
                    'campaign_id' => $order->getAttribute('campaign_id'),
                    'created_by'  => $author,
                ]);

                // Main order only
                unset(
                    $order_data['notes'],
                    $order_data['cc_type'],
                    $order_data['cc_encrypted'],
                    $order_data['cc_first_6'],
                    $order_data['cc_last_4'],
                    $order_data['cc_length'],
                    $order_data['fulfillment_number'],
                    $order_data['is_chargeback'],
                    $order_data['is_fraud'],
                    $order_data['rebill_depth']
                );
            } else {
                // Forward creation to upsell model
                $upsell = Upsell::createFromOrder($order, $product_data);

                if (isset($upsell_totals)) {
                    foreach ($upsell_totals as $model) {
                        $model->setAttribute('upsell_id', $upsell->getAttribute('id'));
                        $model->setCurrencyId($currency_id)->save();
                    }
                }
            }

            $subscription_data['type_id'] = $i ? BillingModelOrderSubscription::TYPE_UPSELL : BillingModelOrderSubscription::TYPE_MAIN;
            $subscription_data['subscription_id'] = $subscription->id;
            $subscription_data['order_id'] = $i ? $upsell->getAttribute('id') : $order_id;
            $subscription_data['offer_id'] = $product_data['offer_id'];
            $subscription_data['created_by'] = $author;
            $subscription_data['cycles_remaining'] = 0; // @todo

            $orderSub = BillingModelOrderSubscription::create($subscription_data);

            foreach ($aggregate_totals + $singular_totals as $model) {
                if (! is_null($model)) {
                    $model->setAttribute('orders_id', $order_id);
                    $model->setCurrencyId($currency_id)->save();
                }
            }

            $prepaid_meta = null;

            //  @todo prepaid meta for billing model note
            if (false) {
                $prepaid_meta = (object) [
                    'is_subscription' => $this->is_prepaid_subscription,
                    'cycles'          => $this->prepaid_cycles,
                    'discount'        => $this->product_subtotal_discount,
                ];
            }

            // @todo don't use legacy note generator
            $bm_note = new \billing_models\history_note([
                'product_id'             => $product_data['products_id'],
                'offer_id'               => $orderSub->offer_id,
                'frequency_id'           => $orderSub->billing_model_id,
                'next_recurring_date'    => $order_data['recurring_date'],
                'next_recurring_product' => 0,
                'is_straight_sale'       => (int) $orderSub->is_straight_sale,
                'prepaid'                => $prepaid_meta,
            ]);

            OrderHistoryNote::create([
                'order_id'    => $order_id,
                'user_id'     => $author,
                'type'        => $bm_note->type,
                'status'      => $bm_note->status,
                'campaign_id' => $order_data['campaign_id'],
                'created_at'  => $order->getAttribute('created_at'),
            ]);
        }

        return $order;
    }

    public function promoteToSubscription()
    {
        // Already processed, possibly as part of a subscription
        if ($this->promoted_to) {
            return false;
        }

        $ancestor = null;
        $author   = \get_current_user_id();

        foreach ($this->siblings()->where('batch_id', $this->batch_id)->get() as $i => $sibling) {
            $parent_id = 0;

            if (isset($order)) {
                $parent_id = $order->getAttribute('id');

                $sibling->setParentId($parent_id);

                if (isset($ancestor)) {
                    $sibling->setAncestorId($ancestor->getAttribute('id'));
                }
            }

            $order = $sibling->promoteToOrder();

            if (! $i) {
                $ancestor = $order;
            } else {
                OrderHistoryNote::create([
                    'order_id'    => $parent_id,
                    'user_id'     => $author,
                    'type'        => 'recurring',
                    'status'      => $order->getAttribute('id'),
                    'campaign_id' => $this->campaign_id,
                    'created_at'  => $order->getAttribute('created_at'),
                ]);
            }
        }

        // Historical record has identified an existing CRM order to link to
        if ($sibling->subscription_end_at) {
            $end          = Order::find($sibling->subscription_end_at);
            $old_ancestor = $end->ancestor_or_self;
            $old_chain    = Order::where('orders_id', $old_ancestor)->orWhere('ancestor_id', $old_ancestor)->orderBy('orders_id')->get();

            foreach ($old_chain as $j => $old_sib) {
                if (! $j) {
                    // Set the parent of the first order in the existing chain to the last historical order
                    $old_sib->setAttribute('parent_id', $order->getAttribute('id'));

                    // Add a note that the final historical order recurred to the first order in the old sub chain
                    OrderHistoryNote::create([
                        'order_id'    => $order->getAttribute('id'),
                        'user_id'     => $author,
                        'type'        => 'recurring',
                        'status'      => $old_sib->getAttribute('id'),
                        'campaign_id' => $this->campaign_id,
                        'created_at'  => $old_sib->getAttribute('created_at'),
                    ]);
                }

                // Existing orders must relate to the new ancestor
                $old_sib->setAttribute('ancestor_id', $ancestor->getAttribute('id'));
                $old_sib->save();

                // Indicate that the existing orders were linked to historical orders
                OrderHistoryNote::create([
                    'order_id'    => $old_sib->getAttribute('id'),
                    'user_id'     => $author,
                    'type'        => 'historic-link',
                    'status'      => $ancestor->getAttribute('id'),
                    'campaign_id' => $this->campaign_id,
                ]);
            }
        }

        $this->batch->trackIncrement('subscription_count', 1);

        return $this->siblings->pluck('id')->toArray();
    }

    /**
     * @return int
     * @todo determine orders_status
     */
    private function determineStatusId()
    {
        return 2;
    }

    /**
     * @param int $product_id
     * @return mixed
     */
    private function getProductName(int $product_id)
    {
        static $cache = [];

        if (isset($cache[$product_id])) {
            return $cache[$product_id];
        }

        return $cache[$product_id] = ProductDescription::where('products_id', $product_id)->first()->name;
    }

    /**
     * @return bool
     */
    public function getIsSubscriptionAttribute()
    {
        return strlen($this->subscription_group) > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function siblings()
    {
        return $this->hasMany(static::class, 'subscription_group', 'subscription_group');
    }
}
