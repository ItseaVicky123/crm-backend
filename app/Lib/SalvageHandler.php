<?php

namespace App\Lib;

use App\Events\Order\Rebilled;
use App\Facades\SMC;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Class SalvageHandler
 * @package App\Lib
 */
class SalvageHandler
{

    /**
     * @var null
     */
    public $smartRetryClient = null;

    /**
     * @var null
     */
    public $legacySalvageClient = null;

    /**
     * @var bool
     */
    public bool $isSmartRetryInstance = false;

    /**
     * @var bool
     */
    protected bool $isSmartProfileMatched = false;

    /**
     * SalvageHandler constructor.
     */
    public function __construct()
    {
        $this->smartRetryClient     = \App\Services\SmartRetry\Client::make();
        $this->legacySalvageClient  = new \decline_salvage\handler;
        $this->legacySalvageClient->load_default();
        $this->legacySalvageClient->set_stamp(strtotime(date('Y-m-d')));
        $this->isSmartRetryInstance = vas_enabled('SMART_RETRIES') && \App\Models\DeclineManager\SmartProfile::first();
    }

    /**
     * @param $context
     */
    public function getSalvageProfileId(Subscription $subscription, $context, $currentAttemptNumber)
    {
        if ($this->isSmartRetryInstance && $this->isSmartProfileMatched = $this->smartRetryClient->matchSubscriptionProfile($subscription->subscription_id)) {
            $salvageProfileId = $this->smartRetryClient->getProfile()->id;
        } else {
            $salvageClass = ($subscription->isMain() ? '\decline_salvage\rule_bus' : '\decline_salvage\upsell_rule_bus');
            $mainOrder    = ($subscription->isMain() ? $subscription : $subscription->main);
            $this->legacySalvageClient->match_profile(new $salvageClass([
                'step'           => $currentAttemptNumber,
                'order_id'       => $subscription->id,
                'campaign_id'    => $subscription->campaign_id,
                'payment_type'   => $subscription->payment_method,
                'payment_method' => (new \binbase($mainOrder->cc_first_6))->get_payment_method_types(),
                'salvage_mode'   => ($context === Rebilled::SALVAGE_CONTEXT),
                'rebillDepth'    => $subscription->rebill_depth,
            ]));
            $salvageProfileId = $this->legacySalvageClient->getProfileId();
        }

        return $salvageProfileId;
    }

    /**
     * @param \App\Models\Order $order
     * @throws \App\Services\SmartRetry\Exceptions\MissingProfile
     * @throws \App\Services\SmartRetry\Exceptions\MissingSubscription
     * @throws \App\Services\SmartRetry\Exceptions\ProfileDoesNotApplyToSubscription
     */
    public function calculateRetryDateAndSalvageAttempts(Order $order)
    {
        $ordersIds             = [$order->parent_id, $order->id];
        $smartDunningEnhanceOn = SMC::check(SMC::SMART_DUNNING_ENHANCEMENT);

        if ($this->isSmartProfileMatched) {
            try {
                $smartProfile = $this->smartRetryClient->getProfile();

                if (!$smartDunningEnhanceOn) {
                    $salvageAttempts = $smartProfile->attempts;
                    $retryDate       = $this->smartRetryClient->getRetryDate()->toDateString();
                }

                $status = $smartProfile->id .
                    (!$smartDunningEnhanceOn ? "|{$retryDate}|{$salvageAttempts}" : '|awaiting retry date|awaiting retry attempts') .
                    "|{$this->smartRetryClient->getAttemptId()}";

                foreach ($ordersIds as $orderId) {
                    new \history_note(
                        $orderId,
                        User::SYSTEM,
                        'smart-retry-profile',
                        $status,
                        $order->parent->campaign_id
                    );
                }
            } catch (\Exception $e) {
                Log::debug("Smart Retry Exception: {$e->getMessage()}");
            }
        } else {
            $salvageAttempts = $this->legacySalvageClient->get('attempt_cnt');
            $retryDate       = $this->legacySalvageClient->get_retry_date($order->id, $order->parent_id);

            foreach ($ordersIds as $orderId) {
                new \history_note(
                    $orderId,
                    User::SYSTEM,
                    'history-note-ds-profile',
                    sprintf("Using decline manager profile %s (%d)", $this->legacySalvageClient->get('name'), $this->legacySalvageClient->get('id')),
                    $order->parent->campaign_id
                );
            }
        }

        return [$retryDate, $salvageAttempts];
    }
}
