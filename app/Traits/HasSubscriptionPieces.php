<?php

namespace App\Traits;

use App\Models\CascadeForce;
use App\Models\DeclinedCC;
use App\Models\GatewayForce;
use App\Models\Offer\Offer;
use App\Models\OrderProductBundle;
use App\Models\ValueAddService;
use Carbon\Carbon;
use App\Exceptions\CustomModelException;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionHoldType;
use App\Models\Upsell;
use billing_models\api\billing_models_order;
use App\Models\Credits\Subscription as SubscriptionCredit;
use App\Models\BillingModel\SubscriptionCredit as BillingModelSubscriptionCredit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use product\bundle\api_payload as bundlePayload;
use product\bundle\bundler as bundleBundler;
use billing_models\api\next_recurring_date;

/**
 * Trait HasSubscriptionPieces
 * @package App\Traits
 */
trait HasSubscriptionPieces
{
    /**
     * @throws \Exception
     */
    protected function checkInstance()
    {
        if (!$this instanceof Order && !$this instanceof Upsell) {
            throw new \Exception('This trait is only for Order and Upsell');
        }
    }

    /**
     * @return bool
     */
    public function isUpsell(): bool
    {
        return $this instanceof Upsell;
    }

    /**
     * @param bool $isTerminal
     * @return bool
     */
    public function toggleIsTerminal(bool $isTerminal)
    {
        $product = $this->getAttribute('order_product');

        if ($product->is_terminal != $isTerminal) {
            $product->update([
                'is_terminal' => $isTerminal,
            ]);

            $extraType = !$isTerminal ? '-removed' : '';

            $this->addHistoryNote(
                "stop-subscription-next-success{$extraType}",
                $this->getAttribute('subscription_id')
            );
        }

        return true;
    }

    /**
     * @param int $holdType
     * @return bool
     * @throws \App\Exceptions\OrderAttributeImmutableException
     */
    public function stopRecurring($holdType = SubscriptionHoldType::USER, \App\Events\Event $previousEvent = null)
    {
        $this->checkInstance();

        if ($this->getAttribute('is_recurring')) {
            $orderId = $this->getAttribute('order_id');

            $this->update([
                'is_recurring' => 0,
                'is_hold'      => 1,
                'hold_date'    => Carbon::now(),
            ]);
            $this->order_product
                ->update([
                    'hold_type_id' => $holdType,
                ]);

            if ($this instanceof Order) {
                $orderEntity = $this;
                if ($consentId = $this->consent_id) {
                    \nmi_paysafe::cancel_subscription($consentId);
                }

                if ($this->swap()) {
                    $this->addHistoryNote(
                        'recurring-upsell-stopped',
                        $this->getSwappedMainToUpsellId()
                    );
                } else {
                    $this->addHistoryNote(
                        'recurring',
                        'stop'
                    );
                    $this->addHistoryNote(
                        'recurring',
                        'hold'
                    );
                }
            } elseif ($this instanceof Upsell) {
                $orderEntity = $this->main()->first();
                // Prevent addon deletion if this is complete or cancel/stop subscription
                if ($this->is_add_on && ! in_array($holdType, [SubscriptionHoldType::CANCEL, SubscriptionHoldType::COMPLETE], true)) {
                    $this->addHistoryNote(
                        'ninja-upsell-removed',
                        $this->order_product->product_id
                    );
                    $this->delete();
                } else {
                    $this->addHistoryNote(
                        'recurring-upsell-stopped',
                        $this->getAttribute('id')
                    );
                }
            }
            $event = new \App\Events\Order\SubscriptionCancelled(
                $orderEntity,
                $this->getAttribute('subscription_id'),
                $this->order_product->product_id,
                null,
                $previousEvent,
                // If we swapped subscriptions, let it load the new subscription model as current reference is wrong
                $orderEntity->getSwappedMainToUpsellId() ? null : $this
            );

            Event::dispatch($event);
            commonProviderUpdateOrder($orderId, 'cancel');

            return true;
        }

        return false;
    }

    /**
     * @param Product $product
     * @param null    $quantity
     * @param array   $children
     * @param int     $variantId
     * @param bool    $allowProductSwap
     * @return bool
     * @throws CustomModelException
     */
    public function updateRecurringProduct(Product $product, $quantity = null, array $children = [], $variantId = 0, bool $allowProductSwap = false): bool
    {
        $this->checkInstance();

        $bundleBundler = null;
        $bundlePayload = null;
        $orderId       = $this->getAttribute('id');
        $typeId        = $this->getAttribute('type_id');
        $productId     = $product->id;

        $this->handleNextBundle($product, $children);

        if ($subOrder = $this->subscription_order) {
            try {
                if ($subOrder->is_prepaid && !$allowProductSwap) {
                    throw new CustomModelException('subscription.can-not-update-prepaid-recurring-product');
                }

                if (!in_array($productId, $subOrder->offer->products->pluck('id')->toArray())) {
                    throw new CustomModelException('subscription.product-not-on-offer');
                }

                $subscriptionOrder = new billing_models_order($orderId, $typeId);

                if ($quantity) {
                    $subscriptionOrder->set_next_recurring_quantity($quantity);
                }

                if (!$subscriptionOrder->update_next_recurring_product($productId, $variantId, $allowProductSwap)) {
                    throw new CustomModelException('subscription.next-recurring-product-update-failed');
                }
            } catch (\Exception $e) {
                if ($e instanceof CustomModelException) {
                    throw $e;
                }

                throw new CustomModelException('subscription.next-recurring-product-update-failed', ['error' => $e->getMessage()]);
            }
        } else {
            // Nutra
            //
            if (!in_array($productId, $this->campaign->products->pluck('id')->toArray())) {
                throw new CustomModelException('subscription.product-not-on-campaign');
            }

            $this->update([
                'custom_rec_prod_id' => $productId,
            ]);
        }

        $this->addHistoryNote(
            'recurring-product-updated',
            "{$this->getAttribute('subscription_id')}:{$productId}"
        );

        return true;
    }

    /**
     * @param $billingModelId
     * @throws CustomModelException
     * @throws \Exception
     */
    public function updateBillingModel($billingModelId)
    {
        $this->checkInstance();

        if ($subOrder = $this->subscription_order) {
            try {
                if (! $subOrder->offer->hasBillingModel($billingModelId)) {
                    throw new CustomModelException('subscription.billing-model-not-on-offer');
                }

                if ($subOrder->offer->isDefaultBillingModel($billingModelId)) {
                    throw new CustomModelException('subscription.billing-model-not-replace-straight-sale');
                }

                if ($subOrder->offer->isDefaultBillingModel($subOrder->billing_model_id)) {
                    throw new CustomModelException('subscription.straight-sale-billing-model-restrict-update');
                }

                $fromBillingModelId = $subOrder->billing_model_id;
                $subscriptionOrder  = new billing_models_order($this->getAttribute('id'),
                    $this->getAttribute('type_id'));

                if (!$subscriptionOrder->update_billing_model($billingModelId)) {
                    throw new CustomModelException('subscription.billing-model-update-failed');
                }

                $this->addHistoryNote(
                    'billing-model-updated',
                    "{$this->getAttribute('subscription_id')}:{$fromBillingModelId}:{$billingModelId}"
                );
            } catch (\Exception $e) {
                $key  = 'subscription.billing-model-update-failed';
                $data = [];

                if ($e instanceof CustomModelException) {
                    $key = $e->getKey();
                } else {
                    $data = [
                        'error' => $e->getMessage(),
                    ];
                }

                throw new CustomModelException($key, $data);
            }
        } else {
            throw new CustomModelException('subscription.not-billing-model-order');
        }
    }

    /**
     * Unit price discount for each quantity
     *
     * @param $amount
     * @throws CustomModelException
     */
    public function updateStickyAmount($amount)
    {
        if ($subOrder = $this->subscription_order) {
            $subOrder->update([
                'sticky_discount_flat_amount' => $amount,
                'sticky_discount_percent'     => 0,
                'updated_by'                  => get_current_user_id(),
            ]);

            $this->addHistoryNote(
                'discount-flat-amount',
                "{$this->getAttribute('subscription_id')}:{$amount}"
            );
        } else {
            // Nutra
            //
            throw new CustomModelException('subscription.not-billing-model-order');
        }
    }

    /**
     * Unit price discount for each quantity
     *
     * @param $percent
     * @throws CustomModelException
     */
    public function updateStickyPercent($percent)
    {
        if ($subOrder = $this->subscription_order) {
            $subOrder->update([
                'sticky_discount_flat_amount' => 0,
                'sticky_discount_percent'     => $percent,
                'updated_by'                  => get_current_user_id(),
            ]);

            $this->addHistoryNote(
                'discount-percent',
                "{$this->getAttribute('subscription_id')}:{$percent}"
            );
        } else {
            // Nutra
            //
            throw new CustomModelException('subscription.not-billing-model-order');
        }
    }

    /**
     * @throws CustomModelException
     */
    public function destroyDiscount()
    {
        if ($subOrder = $this->subscription_order) {
            $subOrder->update([
                'sticky_discount_flat_amount' => 0,
                'sticky_discount_percent'     => 0,
                'updated_by'                  => get_current_user_id(),
            ]);

            $this->addHistoryNote(
                'discount-removed',
                $this->getAttribute('subscription_id')
            );
        } else {
            // Nutra
            //
            throw new CustomModelException('subscription.not-billing-model-order');
        }
    }

    /**
     * @param int   $offerId
     * @param int   $prepaidCycles
     * @param int   $position
     * @param int   $productId
     * @param array $children
     * @return mixed
     * @throws CustomModelException
     * @throws \Exception
     */
    public function updateOffer($offerId, $prepaidCycles = 0, $position = 0, $productId = 0, $quantity = null, $children = [])
    {
        $this->checkInstance();

        if ($subOrder = $this->subscription_order) {
            if ($this->offerAllowed($offerId)) {
                $subId    = $this->subscription_id;
                $offer    = Offer::findOrFail($offerId);
                $original = $subOrder->offer_id;
                $updates  = [
                    'offer_id'                => $offerId,
                    'original_offer_id'       => $original,
                    'prepaid_cycles'          => $prepaidCycles,
                    'updated_by'              => get_current_user_id(),
                    'is_prepaid'              => $offer->is_prepaid,
                    'is_prepaid_subscription' => $offer->is_prepaid_subscription,
                    'next_recurring_product'  => $productId,
                ];

                if ($offer->is_prepaid) {
                    if (!$offer->prepaid_profile->hasTermCycleOption($prepaidCycles)) {
                        throw new CustomModelException('subscription.invalid-prepaid-cycles');
                    }
                }

                if ($offer->is_seasonal && ($products = $offer->seasonal_products)) {
                    $products->each(function ($product) use ($position, &$productId) {
                        if ($product->position == $position) {
                            $productId = $product->product_id;
                        }
                    });

                    if (!$productId) {
                        throw new CustomModelException('subscription.invalid-position');
                    }

                    $updates['cycle_depth']            = $position - 2;
                    $updates['next_recurring_product'] = $productId;
                } elseif (!$offer->hasProduct($productId)) {
                    throw new CustomModelException('subscription.product-not-on-offer');
                } elseif ($quantity) {
                    $updates['next_recurring_quantity'] = $quantity;
                }

                $this->handleNextBundle(Product::findOrFail($productId), $children);

                if ($success = $subOrder->update($updates)) {
                    $this->addHistoryNote('offer-updated', "{$subId}:{$original}:{$offerId}");

                    if ($productId) {
                        $this->addHistoryNote('recurring-product-updated', "{$subId}:{$productId}");
                    }
                }

                return $success;
            }

            throw new CustomModelException('subscription.invalid-offer-id-update');
        }

        throw new CustomModelException('subscription.not-billing-model-order');
    }

    /**
     * @param Product $product
     * @param array   $children
     * @throws CustomModelException
     */
    protected function handleNextBundle(Product $product, $children = [])
    {
        if ($product->is_bundle) {
            if ($product->is_custom_bundle) {
                if (!count($children)) {
                    throw new CustomModelException('products.bundle-children-required');
                }
            }

            $bundleBundler = new bundleBundler([
                'order_id' => $this->id,
                'type_id'  => $this->type_id,
            ]);

            try {
                $bundlePayload = new bundlePayload(
                    $product->id,
                    ($product->is_custom_bundle ? $children : [])
                );
                $bundleBundler->set_main_flag($this instanceof Order);
                $bundleBundler->set_order_id($this->order_id);
                $bundleBundler->set_bundle_id($this->order_product->product->id);
                $bundleBundler->load_collection_by_product();
                $bundleBundler->destroy_next_bundle();
                $bundleBundler->save_entry(
                    $this->id,
                    $this->type_id,
                    $bundlePayload,
                    null,
                    true
                );
            } catch (\Exception $e) {
                throw new CustomModelException('products.invalid-bundle-children', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param $offerId
     * @return bool
     */
    protected function offerAllowed($offerId)
    {
        $available = $this->campaign
            ->offers
            ->pluck('id')
            ->toArray();

        return in_array($offerId, $available);
    }

    /**
     * @return bool
     */
    public function consumeOverride()
    {
        if ($override = $this->subscription_override) {
            $override->update([
                'consumed_at' => Carbon::now(),
            ]);
        }

        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function pauseSubscription()
    {
        $this->checkInstance();
        $this->update([
            'is_recurring' => 0,
            'is_hold'      => 1,
            'hold_date'    => Carbon::now(),
        ]);

        $this->order_product->update([
            'hold_type_id' => SubscriptionHoldType::MERCHANT,
        ]);

        $this->addHistoryNote(
            'subscription-merchant-hold',
            $this->order_product->product_id
        );

        if (ValueAddService::isEnabled(\value_add_service_entry::BIGCOMMERCE)) {
            Event::dispatch(new \App\Events\Subscription\SubscriptionPaused($this));
        }

        return true;
    }

    /**
     * @param      $amount
     * @param bool $replace
     * @throws \Exception
     */
    public function handleSubscriptionCredit($amount, $replace = false)
    {
        $this->checkInstance();
        $credit = $this->subscription_credit ?? SubscriptionCredit::withTrashed()
            ->forSubscriptionId($this->subscription_id)
            ->first();

        if ($credit) {
            if ($credit->trashed()) {
                $credit->restore();
            } elseif (!$replace) {
                $amount += $credit->amount;
            }

            $credit->update([
                'amount' => $amount,
            ]);
        } else {
            $credit = $this->subscription_credit()
                ->create([
                    'amount' => $amount,
                ]);
        }

        $this->addHistoryNote(
            'subscription-credit-2-updated',
            "{$credit->item_id}:{$credit->amount}"
        );
    }

    /**
     * @param $paymentSourceCardType
     * @return bool
     */
    public function paymentMethodAllowed($paymentSourceCardType)
    {
        return in_array(
            $paymentSourceCardType,
            $this->campaign->payment_methods->pluck('value')->toArray()
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function gateway_force()
    {
        $relationship = $this->hasOne(GatewayForce::class, 'orders_id');

        if ($this instanceof Upsell) {
            $relationship->where('is_upsell', 1);
        }

        return $relationship;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function check_force()
    {
        $relationship = $this->hasOne(CheckForce::class, 'orders_id');

        if ($this instanceof Upsell) {
            $relationship->where('is_upsell', 1);
        }

        return $relationship;
    }

    /**
     * @param     $type
     * @param     $message
     * @param int $userId
     */
    public function addHistoryNote($type, $message, $userId = 0)
    {
        if (!$userId) {
            $userId = get_current_user_id();
        }

        $main = $this instanceof Upsell ? $this->main : $this;

        $main->history_notes()
            ->create([
                'user_id'   => $userId,
                'type_name' => $type,
                'message'   => $message,
            ]);
    }

    /**
     * @param      $profileId
     * @param      $batchId
     * @param      $userId
     * @param bool $preserve
     * @param bool $isChecking
     * @return $this
     */
    public function handleForce($profileId, $batchId, $userId, $preserve = false, $isChecking = false)
    {
        CascadeForce::destroy($this->main_order_id);

        GatewayForce::updateOrCreate(
            [
                'orders_id'      => $this->id,
                'main_orders_id' => $this->main_order_id,
            ],
            [
                'is_upsell'  => $this instanceof Upsell,
                'batch_id'   => $batchId,
                'preserve'   => $preserve,
                'admin_id'   => $userId,
                'is_check'   => $isChecking,
                'gateway_id' => $profileId,
            ]
        );

        return $this;
    }

    /**
     * @param        $profileId
     * @param        $from
     * @param        $userId
     * @param string $extraHistoryInfo
     * @return $this
     */
    public function handleForceNote($profileId, $from, $userId, $extraHistoryInfo = '')
    {
        $productId = $this->order_product->product_id;
        $this->addHistoryNote('force-gateway-type', "{$profileId}:{$productId}:{$from}:{$extraHistoryInfo}",
            $userId);

        return $this;
    }

    /**
     * Not used yet but prepped for future
     */
    public function removeForce()
    {
        if ($force = GatewayForce::where('orders_id', $this->id)->where('main_orders_id', $this->main_order_id)->first()) {
            $force->delete();
            $productId = $this->order_product->product_id;
            $this->addHistoryNote('force-gateway-removed', "{$productId}:{$force->gateway_id}");
        }
    }

    /**
     * @param $query
     * @param array $includeStatuses
     * @return mixed
     */
    public function scopeRecurring($query, array $includeStatuses = [])
    {
        return $query->where([
            ['is_recurring', '=', 1],
            ['is_hold', '=', 0],
            ['is_archived', '=', 0],
        ])
            ->whereIn('status_id', [2, 6, 8, ...$includeStatuses]);
    }

    /**
     * This is an attempt to standardize the MANUAL updating of what the system views, in different places,
     * as the 'billing date', or 'next recurring date', or 'retry date'. These are the same date but
     * the value is conditional based on the recurring date and the presence of a retry date.
     *
     * @param string $date
     * @param bool $useNewDay
     * @return bool
     */
    public function updateRecurringDate(string $date, bool $useNewDay = false): bool
    {

        // Validate date format
        if (!$this->validRecurringDate($date)) {
            return false;
        }

        // If we have a retry date, update it
        if ($this->hasRetryDate()) {
            $prop = $this->isUpsell() ? 'retry_at' : 'date_purchased';
            return $this->update([$prop => $date]);
        }

        // Otherwise update recurring_date
        if ((request()->use_new_day == 1 || $useNewDay) && $this->order_subscription && $this->order_subscription->bill_by_type_id == next_recurring_date::BILL_BY_RELATIVE_DAY) {
            $this->order_subscription->update(['bill_by_days' => date('d', strtotime($date))]);
        }
        return $this->update(['recurring_date' => $date]);

    }

    /**
     * Do I have a non-empty retry date (from 'date_purchased' column in the database)
     *
     * @return bool
     */
    public function hasRetryDate(): bool
    {
        /** 'date_purchased' is mapped to 'retry_at' on Order and Upsell models during instantiation */
        return isset($this->retry_at) && $this->retry_at->year > 1;
    }

    /**
     * Is the given date in a valid format to be used as my recurring date?
     * Check for YYYY-MM-DD format
     *
     * @param string $date
     * @return bool
     */
    public function validRecurringDate(string $date): bool
    {
        // Ensure YYYY-MM-DD format
        if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $parts)) {
            return checkdate($parts[2], $parts[3], $parts[1]);
        }

        return false;
    }

    /**
     * Attempt to fetch my BillingModelSubscriptionCredit
     *
     * @return \App\Models\BillingModel\SubscriptionCredit|null
     */
    public function getBillingModelSubscriptionCredit(): ?BillingModelSubscriptionCredit
    {
        if ($subOrder = $this->subscription_order()->first()) {
            return $subOrder->billing_model_subscription_credit()->first();
        }

        return null;
    }

    /**
     * Intelligently fetch the date of my next billing attempt
     *
     * @param string $format
     * @return string
     */
    public function getDateOfNextBillingAttempt(string $format = "Y-m-d"): string
    {
        $recur = $this->recurring_date; // Where we store recurring date
        $retry = $this->date_purchased; // Where we store retry date for some reason

        // If we have a valid retry date, return that
        if ($retry->year > 1) {
            return $retry->format($format);
        }

        // Otherwise, return the recurring date
        return $recur->format($format);
    }

    /**
     * Fetch after next recurring date.
     * This method is used when we want to skip next shipment
     * and that means that the recurring date will need to be rescheduled to the next one based on the BM configs
     *
     * @return string
     */
    public function getAfterNextRecurringDate(): string {
        $frequencyDates = [];
        $sub            = $this->order_subscription;

        // These dates are used for bill by schedule type of BM only
        if ($sub->billing_model && $dates = $sub->billing_model->dates) {
            foreach ($dates as $date) {
                $frequencyDates[$date->month][$date->day] = true;
            }
        }

        return (string) new next_recurring_date([
            'start_date'      => $this->next_valid_recurring_date->format('Y-m-d'),
            'bill_by_type_id' => $sub->bill_by_type_id,
            'bill_by_days'    => $sub->bill_by_days,
            'interval_day'    => $sub->interval_day,
            'interval_week'   => $sub->interval_week,
            'frequency_dates' => $frequencyDates
        ]);
    }
}
