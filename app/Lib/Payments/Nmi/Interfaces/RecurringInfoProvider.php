<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface RecurringInfoProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getRecurring(): ?string;

    /**
     * @param string|null $recurring
     * @return self
     */
    public  function setRecurring(?string $recurring): self;

    /**
     * @return string|null
     */
    public  function getPlanId(): ?string;

    /**
     * @param string|null $planId
     * @return self
     */
    public  function setPlanId(?string $planId): self;

    /**
     * @return int|null
     */
    public  function getPlanPayments(): ?int;

    /**
     * @param int|null $planPayments
     * @return self
     */
    public  function setPlanPayments(?int $planPayments): self;

    /**
     * @return string|null
     */
    public  function getPlanAmount(): ?string;

    /**
     * @param string|null $planAmount
     * @return self
     */
    public  function setPlanAmount(?string $planAmount): self;

    /**
     * @return int|null
     */
    public  function getDayFrequency(): ?int;

    /**
     * @param int|null $dayFrequency
     * @return self
     */
    public  function setDayFrequency(?int $dayFrequency): self;

    /**
     * @return int|null
     */
    public  function getMonthFrequency(): ?int;

    /**
     * @param int|null $monthFrequency
     * @return self
     */
    public  function setMonthFrequency(?int $monthFrequency): self;

    /**
     * @return int|null
     */
    public  function getDayOfMonth(): ?int;

    /**
     * @param int|null $dayOfMonth
     * @return self
     */
    public  function setDayOfMonth(?int $dayOfMonth): self;

    /**
     * @return string|null
     */
    public  function getStartDate(): ?string;

    /**
     * @param string|null $startDate
     * @return self
     */
    public  function setStartDate(?string $startDate): self;

}
