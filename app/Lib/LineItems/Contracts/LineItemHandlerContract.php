<?php


namespace App\Lib\LineItems\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LineItemHandlerContract
 * @package App\Lib\LineItems\Contracts
 */
class LineItemHandlerContract
{
    /**
     * @var int $orderId
     */
    protected int $orderId = 0;

    /**
     * @var int $orderTypeId
     */
    protected int $orderTypeId = 0;

    /**
     * @var Model|null $orderModel
     */
    protected ?Model $orderModel = null;

    /**
     * @return static
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Get an instance with order ID and order type ID set
     * @param int $orderId
     * @param $orderTypeId
     * @return $this
     */
    public function withOrderIdAndType(int $orderId, $orderTypeId = ORDER_TYPE_MAIN): self
    {
        return $this
            ->setOrderId($orderId)
            ->setOrderTypeId($orderTypeId);
    }

    /**
     * Get instance with model set
     * @param Model $orderModel
     * @return $this
     */
    public function withModel(Model $orderModel): self
    {
        return $this->setOrderModel($orderModel);
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     * @return LineItemHandlerContract
     */
    public function setOrderId(int $orderId): LineItemHandlerContract
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrderTypeId(): int
    {
        return $this->orderTypeId;
    }

    /**
     * @param int $orderTypeId
     * @return LineItemHandlerContract
     */
    public function setOrderTypeId(int $orderTypeId): self
    {
        $this->orderTypeId = $orderTypeId;

        return $this;
    }

    /**
     * @return Model|null
     */
    public function getOrderModel(): ?Model
    {
        return $this->orderModel;
    }

    /**
     * @param Model|null $orderModel
     * @return LineItemHandlerContract
     */
    public function setOrderModel(?Model $orderModel): self
    {
        $this->orderModel = $orderModel;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasModel(): bool
    {
        return (bool) $this->orderModel;
    }
}
