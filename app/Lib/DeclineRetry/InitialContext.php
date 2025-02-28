<?php

namespace App\Lib\DeclineRetry;

use App\Models\OrderHistoryNote;
use App\Models\OrderAttributes;
use App\Models\Upsell;
use hard_decline;
use decline_salvage\rule_bus;
use binbase;
use App\Models\ConfigSetting;
use App\Models\DeclineRetry\DeclineRetryJourney;
use App\Facades\SMC;

/**
 * Class InitialContext
 * @package App\Services\Dunning
 */
class InitialContext extends AbstractContext
{
    /**
     * @var bool $isHardDecline
     */
    protected bool $isHardDecline = false;

    /**
     * @var bool $isDeclineModuleBlocked
     */
    protected bool $isDeclineModuleBlocked = false;

    /**
     * @var array $platformGatewayResponse
     */
    protected array $platformGatewayResponse;

    /**
     * @var string $retryDate
     */
    protected string $retryDate = '';

    /**
     * @var int|null $matchedProfileId
     */
    protected ?int $matchedProfileId = null;

    /**
     * @var int|null $salvageAttempts
     */
    protected ?int $salvageAttempts = null;

    /**
     * @var bool $isSuccessful
     */
    protected bool $isSuccessful = false;

    /**
     * InitialContext constructor.
     * @param int $newDeclineOrderId
     * @param array $platformGatewayResponse
     */
    public function __construct(int $newDeclineOrderId, array $platformGatewayResponse)
    {
        parent::__construct($newDeclineOrderId);
        $this->platformGatewayResponse = $platformGatewayResponse;

        try {
            $this->initializeClients();

            // Determine if platform gateway response is eligible for dunning
            //
            if ($this->targetOrderModel && \declined_response_code($this->platformGatewayResponse)) {
                $subscriptionId = $this->targetOrderModel->subscription_id;
                $campaignId     = $this->targetOrderModel->campaign_id;
                $creditCardType = $this->targetOrderModel->payment_method;
                $firstSix       = $this->targetOrderModel->cc_first_6;

                // Determine decline salvage client based upon configuration
                //
                $currentSalvageClient = null;
                $matchedProfileId     = 0;

                if ($this->isSmartRetriesEnabled && ($this->isSmartRetry = $this->smartRetryClient->matchSubscriptionProfile($subscriptionId))) {
                    $currentSalvageClient = $this->smartRetryClient;
                } else {
                    $currentSalvageClient = $this->salvageClient;
                    $currentSalvageClient->match_profile(new rule_bus([
                        'step'            => 1,
                        'order_id'        => $this->targetOrderId,
                        'campaign_id'     => $campaignId,
                        'payment_type'    => $creditCardType,
                        'payment_method'  => (new binbase($firstSix))->get_payment_method_types(),
                        'salvage_mode'    => false,
                        'rebillDepth'     => 0,
                        'isInitial'       => true,
                    ]));
                    $matchedProfileId = $currentSalvageClient->getProfileId();
                }

                // Determine if it is a gateway specific hard decline
                //
                $this->isHardDecline = \gateway_hard_decline($this->platformGatewayResponse);

                // Determine if the decline module prevents dunning
                //
                $declineModule = new hard_decline\action($this->targetOrderId);
                $declineModule->set_gateway_reason($this->platformGatewayResponse['errorMessage']);
                $declineModule->match_gateway_reason();
                $this->isDeclineModuleBlocked = (! $declineModule->allow_action(hard_decline\models\hard_decline_rule::ACTION_DECLINE_SALVAGE));

                if (! $this->isHardDecline && ! $this->isDeclineModuleBlocked) {
                    OrderHistoryNote::create([
                        'order_id'   => $this->targetOrderId,
                        'message'    => 'Enabling initial dunning on this decline order.',
                        'type_name'  => 'initial-dunning-enabled',
                        'author'     => $this->systemAdminId,
                    ]);

                    // Fetch related upsells
                    //
                    $upsellsBundled = $this->targetOrderModel->additional_products;

                    // Create order history notes for following retry handler types
                    //
                    if ($this->isSmartRetry) {
                        $smartProfile          = $currentSalvageClient->getProfile();
                        $this->salvageAttempts = $smartProfile->attempts;
                        $this->retryDate       = $currentSalvageClient->getRetryDate()->toDateString();
                        $matchedProfileId      = $smartProfile->id;
                        OrderHistoryNote::create([
                            'order_id'   => $this->targetOrderId,
                            'message'    => $smartProfile->id . "|{$this->retryDate}|{$this->salvageAttempts}|{$currentSalvageClient->getAttemptId()}",
                            'type_name'  => 'smart-retry-profile',
                            'author'     => $this->systemAdminId,
                        ]);
                    } else {
                        // Default salvage handler path
                        //
                        $this->salvageAttempts = $currentSalvageClient->get('attempt_cnt');
                        $this->retryDate       = $currentSalvageClient->get_retry_date($this->targetOrderId);
                        OrderHistoryNote::create([
                            'order_id'   => $this->targetOrderId,
                            'message'    => sprintf("Using decline manager profile %s (%d)", $currentSalvageClient->get('name'), $currentSalvageClient->get('id')),
                            'type_name'  => 'history-note-ds-profile',
                            'author'     => $this->systemAdminId,
                        ]);
                        $currentSalvageClient->force_gateway($this->newOrderGatewayId, $this->targetOrderId, $campaignId);
                    }

                    // If there is a match and a retry date then set this order up for initial dunning
                    //
                    if ($matchedProfileId && $this->retryDate) {
                        $this->matchedProfileId = $matchedProfileId;
                        $declineRetryJourney    = DeclineRetryJourney::create([
                            'profile_id'    => $matchedProfileId,
                            'retry_type_id' => $this->retryTypeId,
                            'order_id'      => $this->targetOrderId,
                            'order_type_id' => 1 // @todo use global constants ORDER_TYPE_MAIN https://sticky.atlassian.net/browse/DEV-1135
                        ]);
                        $orderRecurring   = $this->targetOrderModel->update(['is_recurring' => 1]);
                        $attributeCreated = OrderAttributes\InitialDunning::createForOrder($this->targetOrderId, 1);

                        if ($declineRetryJourney && $orderRecurring && $attributeCreated) {
                            $this->isSuccessful = true;

                            // Legacy recurring offset function
                            //
                            \SetRecurringOffset($this->targetOrderId, $this->retryDate);

                            // Process any related upsells
                            //
                            if (count($upsellsBundled)) {
                                // Set the declined upsells to recurring
                                // NOTE: We are also updating recurring_date only for upsells because we want the system to bill them
                                // together upon retry attempt. The bundled upsell query does not look at date_purchased when recurring for some reason.
                                //
                                Upsell::whereIn('main_orders_id', [$this->targetOrderId])
                                    ->update([
                                        'is_recurring'   => 1,
                                        'recurring_date' => $this->retryDate
                                    ]);
                                /**
                                * @var Upsell $upsell
                                */
                                foreach ($upsellsBundled as $upsell) {
                                    \SetRecurringOffset([$this->targetOrderId, $upsell->upsell_orders_id], $this->retryDate);
                                    DeclineRetryJourney::create([
                                        'profile_id'    => $matchedProfileId,
                                        'retry_type_id' => $this->retryTypeId,
                                        'order_id'      => $upsell->upsell_orders_id,
                                        'order_type_id' => 2 // @todo use global constants ORDER_TYPE_UPSELL https://sticky.atlassian.net/browse/DEV-1135
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    throw new \Exception("Initial dunning not supported for hard declines or decline module blocked responses");
                }
            }
        } catch (\Exception $e) {
            \fileLogger::notification("Exception caught while processing: {$e->getMessage()}", __METHOD__, LOG_ERROR);
        }
    }

    /**
     * Process initial dunning entry point if feature enabled.
     * @param int $targetOrderId
     * @param array $newOrderResponse
     * @param bool $initialDunningRequested
     * @return InitialContext|null
     */
    public static function process(int $targetOrderId, array $newOrderResponse, bool $initialDunningRequested): ?InitialContext
    {
        if ($initialDunningRequested && \system_module_control::check(SMC::INITIAL_DUNNING)) {
            return new self($targetOrderId, $newOrderResponse);
        }

        return null;
    }

    /**
     * Determine whether or not the order will go through initial dunning.
     * @return bool
     */
    public function meetsDunningCriteria(): bool
    {
        return $this->matchedProfileId && $this->retryDate;
    }

    /**
     * @return string
     */
    public function getRetryDate(): string
    {
        return $this->retryDate;
    }

    /**
     * @return int|null
     */
    public function getMatchedProfileId(): ?int
    {
        return $this->matchedProfileId;
    }

    /**
     * Load the amount of seconds between subsequent declines
     * that allows us to prevent redundant retries
     */
    protected function loadDuplicateSecondsWindow(): void
    {
        if ($config = ConfigSetting::key('INITIAL_DUNNING_DECLINE_DUPE_WINDOW')->first()) {
            $this->duplicateSecondsWindow = min((int) $config->value, self::DUPLICATE_MAX_SECONDS);
        }
    }
}
