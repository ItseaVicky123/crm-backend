<?php


namespace App\Lib\Billing;

use App\Models\OrderHistoryNote;
use App\Models\OrderLineItems\SubscriptionCredit;
use App\Models\User;

/**
 * Class SubscriptionCreditHandler
 * @package App\Lib\Billing
 */
class SubscriptionCreditHandler
{
    const SORT_ORDER = '800';

    /**
     * @var int $orderId
     */
    protected int $orderId;

    /**
     * @var float|null $creditApplied
     */
    protected ?float $creditApplied = null;

    /**
     * @var float|null $creditAvailable
     */
    protected ?float $creditAvailable = null;

    /**
     * @var float|null $creditRemaining
     */
    protected ?float $creditRemaining = null;

    /**
     * @var bool $isApproved
     */
    protected bool $isApproved = false;

    /**
     * @var SubscriptionCredit|null $subscriptionCreditModel
     */
    protected ?SubscriptionCredit $subscriptionCreditModel = null;

    /**
     * SubscriptionCreditHandler constructor.
     * @param int $orderId
     * @param float|null $creditApplied
     * @param float|null $creditAvailable
     * @param float|null $creditRemaining
     */
    public function __construct(int $orderId, ?float $creditApplied, ?float $creditAvailable, ?float $creditRemaining)
    {
        $this->orderId         = $orderId;
        $this->creditApplied   = $creditApplied;
        $this->creditAvailable = $creditAvailable;
        $this->creditRemaining = $creditRemaining;
    }

    /**
     * Save the subscription credit pieces needed before the gateway transaction.
     */
    public function beforeTransactionSave(): void
    {
        $this->subscriptionCreditModel = SubscriptionCredit::updateOrCreate(
            [
                'orders_id' => $this->orderId,
                'class'     => SubscriptionCredit::CLASS_NAME,
            ],
            [
                'value'      => $this->creditApplied,
                'sort_order' => self::SORT_ORDER,
            ]
        );
    }

    /**
     * Save the subscription credit pieces needed after the gateway transaction.
     */
    public function afterTransactionSave(): void
    {
        // Only create the order history note if the gateway approved the transaction.
        //
        if ($this->isApproved) {
            OrderHistoryNote::create([
                'order_id' => $this->orderId,
                'user_id'  => User::SYSTEM,
                'type'     => 'subscription-credit-applied',
                'status'   => "{$this->creditAvailable}:{$this->creditApplied}:{$this->creditRemaining}",
            ]);
        } else if ($this->subscriptionCreditModel) {
            // Delete the subscription credit order total record for declines.
            //
            $this->subscriptionCreditModel->delete();
        }
    }

    /**
     * Set the transaction approval status.
     * @param bool $isApproved
     * @return self
     */
    public function setIsApproved(bool $isApproved): self
    {
        $this->isApproved = $isApproved;

        return $this;
    }
}
