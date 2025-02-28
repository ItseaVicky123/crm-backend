<?php

namespace App\Lib\Orders\SubscriptionOrders;

use Illuminate\Support\Collection;

/**
 * Class SubscriptionOrderUpdateResponse
 * @package App\Lib\Orders\SubscriptionOrders
 */
class SubscriptionOrderUpdateResponse
{
    const UPDATE_TYPE_DATE           = 1;
    const UPDATE_TYPE_PRODUCT        = 2;
    const UPDATE_TYPE_QUANTITY       = 3;
    const UPDATE_TYPE_BILLING_MODEL  = 4;
    const UPDATE_TYPE_PRICE          = 5;
    const UPDATE_TYPE_PRESERVE_PRICE = 6;
    const UPDATE_TYPE_VARIANT        = 7;

    /**
     * @var array $affectedSubscriptionIds
     */
    protected array $affectedSubscriptionIds = [];

    /**
     * @var int $updateType
     */
    protected int $updateType = 0;

    /**
     * @var string $newValue
     */
    protected string $newValue = '';

    /**
     * @var array|string[] $updateTypeMap
     */
    private array $updateTypeMap = [
        self::UPDATE_TYPE_DATE           => 'Next Recurring Date',
        self::UPDATE_TYPE_PRODUCT        => 'Next Recurring Product',
        self::UPDATE_TYPE_QUANTITY       => 'Next Recurring Quantity',
        self::UPDATE_TYPE_BILLING_MODEL  => 'Billing Model',
        self::UPDATE_TYPE_PRICE          => 'Next Recurring Price',
        self::UPDATE_TYPE_PRESERVE_PRICE => 'Recurring Price Preservation',
        self::UPDATE_TYPE_VARIANT        => 'Next Recurring Variant',
    ];

    /**
     * @return $this
     */
    public function setUpdateTypeRecurringDate(): self
    {
        $this->updateType = self::UPDATE_TYPE_DATE;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypeProduct(): self
    {
        $this->updateType = self::UPDATE_TYPE_PRODUCT;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypeVariant(): self
    {
        $this->updateType = self::UPDATE_TYPE_VARIANT;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypeQuantity(): self
    {
        $this->updateType = self::UPDATE_TYPE_QUANTITY;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypeBillingModel(): self
    {
        $this->updateType = self::UPDATE_TYPE_BILLING_MODEL;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypePrice(): self
    {
        $this->updateType = self::UPDATE_TYPE_PRICE;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUpdateTypePreservePrice(): self
    {
        $this->updateType = self::UPDATE_TYPE_PRESERVE_PRICE;

        return $this;
    }

    /**
     * @return array
     */
    public function getAffectedSubscriptionIds(): array
    {
        return $this->affectedSubscriptionIds;
    }

    /**
     * @param string $subscriptionId
     */
    public function pushAffectedSubscriptionId(string $subscriptionId): void
    {
        $this->affectedSubscriptionIds[] = $subscriptionId;
    }

    /**
     * @param array $affectedSubscriptionIds
     * @return SubscriptionOrderUpdateResponse
     */
    public function setAffectedSubscriptionIds(array $affectedSubscriptionIds): self
    {
        $this->affectedSubscriptionIds = $affectedSubscriptionIds;

        return $this;
    }

    /**
     * @return int
     */
    public function getUpdateType(): int
    {
        return $this->updateType;
    }

    /**
     * @param int $updateType
     * @return SubscriptionOrderUpdateResponse
     */
    public function setUpdateType(int $updateType): self
    {
        $this->updateType = $updateType;

        return $this;
    }

    /**
     * @return string
     */
    public function getNewValue(): string
    {
        return $this->newValue;
    }

    /**
     * @param string $newValue
     * @return SubscriptionOrderUpdateResponse
     */
    public function setNewValue(string $newValue): self
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * Return object payload as a collection of data.
     */
    public function toCollection(): Collection
    {
        return collect([
            'affected_subscription_ids' => $this->affectedSubscriptionIds,
            'update_type'               => $this->updateTypeToString(),
            'new_value'                 => $this->newValue,
        ]);
    }

    /**
     * Convert the int constant to a string.
     * @return string
     */
    private function updateTypeToString(): string
    {
        $typeString = 'Unknown';

        if (isset($this->updateTypeMap[$this->updateType])) {
            $typeString = $this->updateTypeMap[$this->updateType];
        }

        return $typeString;
    }
}
