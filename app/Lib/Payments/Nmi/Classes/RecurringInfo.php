<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\RecurringInfoProvider;

class RecurringInfo implements RecurringInfoProvider
{

    public const RECURRING_ACTION_ADD_SUBSCRIPTION = 'add_subscription';

    /**
     * @var string|null
     */
    protected ?string $recurring = self::RECURRING_ACTION_ADD_SUBSCRIPTION;

    /**
     * Create a subscription tied to a Plan ID if the sale/auth transaction is successful.
     * @var string|null
     */
    protected ?string $planId = null;

    /**
     * The number of payments before the recurring plan is complete.
     * Note: Use 0 for 'until canceled'
     * @var int|null
     */
    protected ?int $planPayments = null;

    /**
     * The plan amount to be charged each billing cycle.
     * Format: x.xx
     * @var string|null
     */
    protected ?string $planAmount = null;

    /**
     * How often, in days, to charge the customer.
     * Cannot be set with 'monthFrequency' or 'dayOfMonth'.
     * @var int|null
     */
    protected ?int $dayFrequency = null;

    /**
     * How often, in months, to charge the customer.
     * Cannot be set with 'dayFrequency'.
     * Must be set with 'dayOfMonth'.
     * Values: 1 through 24
     * @var int|null
     */
    protected ?int $monthFrequency = null;

    /**
     * The day that the customer will be charged.
     * Cannot be set with 'dayFrequency'.
     * Must be set with 'monthFrequency'.
     * Values: 1 through 31 - for months without 29, 30, or 31 days, the charge will be on the last day
     * @var int|null
     */
    protected ?int $dayOfMonth = null;

    /**
     * The first day that the customer will be charged.
     * Format: YYYYMMDD
     * @var string|null
     */
    protected ?string $startDate = null;

    /**
     * @return string|null
     */
    public function getRecurring(): ?string
    {
        return $this->recurring;
    }

    /**
     * @param string|null $recurring
     * @return RecurringInfoProvider
     */
    public function setRecurring(?string $recurring): RecurringInfoProvider
    {
        $this->recurring = $recurring;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlanId(): ?string
    {
        return $this->planId;
    }

    /**
     * @param string|null $planId
     * @return RecurringInfoProvider
     */
    public function setPlanId(?string $planId): RecurringInfoProvider
    {
        $this->planId = $planId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlanPayments(): ?int
    {
        return $this->planPayments;
    }

    /**
     * @param int|null $planPayments
     * @return RecurringInfoProvider
     */
    public function setPlanPayments(?int $planPayments): RecurringInfoProvider
    {
        $this->planPayments = $planPayments;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlanAmount(): ?string
    {
        return $this->planAmount;
    }

    /**
     * @param string|null $planAmount
     * @return RecurringInfoProvider
     */
    public function setPlanAmount(?string $planAmount): RecurringInfoProvider
    {
        $this->planAmount = $planAmount;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDayFrequency(): ?int
    {
        return $this->dayFrequency;
    }

    /**
     * @param int|null $dayFrequency
     * @return RecurringInfoProvider
     */
    public function setDayFrequency(?int $dayFrequency): RecurringInfoProvider
    {
        $this->dayFrequency = $dayFrequency;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMonthFrequency(): ?int
    {
        return $this->monthFrequency;
    }

    /**
     * @param int|null $monthFrequency
     * @return RecurringInfoProvider
     */
    public function setMonthFrequency(?int $monthFrequency): RecurringInfoProvider
    {
        $this->monthFrequency = $monthFrequency;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    /**
     * @param int|null $dayOfMonth
     * @return RecurringInfoProvider
     */
    public function setDayOfMonth(?int $dayOfMonth): RecurringInfoProvider
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    /**
     * @param string|null $startDate
     * @return RecurringInfoProvider
     */
    public function setStartDate(?string $startDate): RecurringInfoProvider
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'recurring'       => $this->recurring,
            'plan_id'         => $this->planId,
            'plan_payments'   => $this->planPayments,
            'plan_amount'     => $this->planAmount,
            'day_frequency'   => $this->dayFrequency,
            'month_frequency' => $this->monthFrequency,
            'day_of_month'    => $this->dayOfMonth,
            'start_date'      => $this->startDate,
        ];
    }
}
