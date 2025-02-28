<?php

namespace App\Lib\DeclineRetry;

use App\Models\DeclineRetry\DeclineRetryJourney;
use App\Models\DeclineRetry\DeclineRetryAttempt;

/**
 * Perform the necessary actions while recurring an order eligible for salvage
 * Class InitialDunningStorage
 * @package App\Lib\DeclineRetry
 */
class InitialDunningStorage
{
    /**
     * The original parent order, which can also be an upsell order.
     * @var int|null $parentOrderId
     */
    protected ?int $parentOrderId = null;

    /**
     * The child decline order ID.
     * @var int|null $childDeclineOrderId
     */
    protected ?int $childDeclineOrderId = null;

    /**
     * Whether or not the original parent order is in the orders or upsell_orders table.
     * @var bool $parentIsUpsell
     */
    protected bool $parentIsUpsell = false;

    /**
     * Decline manager profile ID.
     * @var int $profileId
     */
    protected int $profileId = 0;

    /**
     * @var DeclineRetryJourney|null $journey
     */
    protected ?DeclineRetryJourney $journey = null;

    /**
     * @var int|null $nextAttemptNumber
     */
    protected ?int $nextAttemptNumber = null;

    /**
     * @var int|null $lastAttemptNumber
     */
    protected ?int $lastAttemptNumber = null;

    /**
     * Save the initial dunning pieces once all dependencies have been determined.
     */
    public function save(): void
    {
        // Load the original journey
        //
        if ($this->parentOrderId && $this->childDeclineOrderId) {
            if ($journey = $this->fetchJourney()) {
                // Save the retry attempt
                //
                DeclineRetryAttempt::create([
                    'order_type_id'            => 1, // Children will always be of type main
                    'order_id'                 => $this->childDeclineOrderId,
                    'decline_retry_journey_id' => $journey->id,
                    'profile_id'               => $this->profileId,
                    'attempt_number'           => $this->getLastAttemptNumber() + 1,
                ]);
            }
        }
    }

    /**
     * @param int|null $parentOrderId
     */
    public function setParentOrderId(?int $parentOrderId): void
    {
        $this->parentOrderId = $parentOrderId;
    }

    /**
     * @param int|null $childDeclineOrderId
     */
    public function setChildDeclineOrderId(?int $childDeclineOrderId): void
    {
        $this->childDeclineOrderId = $childDeclineOrderId;
    }

    /**
     * @param bool $parentIsUpsell
     */
    public function setParentIsUpsell(bool $parentIsUpsell): void
    {
        $this->parentIsUpsell = $parentIsUpsell;
    }

    /**
     * @param int $profileId
     */
    public function setProfileId(int $profileId): void
    {
        $this->profileId = $profileId;
    }

    /**
     * Whether or not the parent order ID is an initial dunning order.
     * @param int $parentOrderId
     * @param int $orderTypeId
     * @return bool
     */
    public static function isInitialDunningLinked(int $parentOrderId, int $orderTypeId): bool
    {
        return (bool) DeclineRetryJourney::where([
            ['order_id', $parentOrderId],
            ['order_type_id', $orderTypeId]
        ])->count();
    }

    /**
     * @return bool
     */
    public function journeyExists(): bool
    {
        if ($this->fetchJourney()) {
            return true;
        }

        return false;
    }

    /**
     * @return DeclineRetryJourney|null
     */
    private function fetchJourney(): ?DeclineRetryJourney
    {
        if (! $this->journey) {
            $orderTypeId   = $this->parentIsUpsell ? 2 : 1; // @todo use global constants https://sticky.atlassian.net/browse/DEV-1135
            $this->journey = DeclineRetryJourney::where([
                ['order_id', $this->parentOrderId],
                ['order_type_id', $orderTypeId]
            ])->first();
        }

        return $this->journey;
    }

    /**
     * @return int
     */
    public function getLastAttemptNumber(): int
    {
        if (is_null($this->lastAttemptNumber)) {
            $this->lastAttemptNumber = 1;

            if ($journey = $this->fetchJourney()) {
                $this->lastAttemptNumber = DeclineRetryAttempt::where([
                    ['decline_retry_journey_id', $journey->id]
                ])->count();
            }
        }

        return $this->lastAttemptNumber;
    }

    /**
     * @return int
     */
    public function getNextAttemptNumber(): int
    {
        if (is_null($this->nextAttemptNumber)) {
            $this->nextAttemptNumber = $this->getLastAttemptNumber() + 1;
        }

        return $this->nextAttemptNumber;
    }
}
