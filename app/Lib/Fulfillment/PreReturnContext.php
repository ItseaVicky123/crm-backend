<?php


namespace App\Lib\Fulfillment;

use App\Facades\SMC;
use App\Models\Order;
use App\Models\OrderStatus;

/**
 * Class PreReturnContext
 * @package App\Lib\Fulfillment
 */
class PreReturnContext
{
    /**
     * @var Order|null $order
     */
    protected ?Order $order = null;

    /**
     * @var bool $isInventoryActive
     */
    protected bool $isInventoryActive = false;

    /**
     * @var bool $isShipped
     */
    protected bool $isShipped = false;

    /**
     * @var bool $isPending
     */
    protected bool $isPending = false;

    /**
     * @var bool $isDeclined
     */
    protected bool $isDeclined = false;

    /**
     * PreReturnContext constructor.
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        try {
            $this->order             = Order::findOrFail($orderId);
            $this->isInventoryActive = SMC::check(SMC::INVENTORY_AWARENESS);
            $this->isShipped         = $this->order->orders_status == OrderStatus::STATUS_SHIPPED;
            $this->isPending         = $this->order->orders_status == OrderStatus::STATUS_DECLINED;
            $this->isDeclined        = $this->order->orders_status == OrderStatus::STATUS_PENDING;
        }
        catch(\Exception $e) {
            \filelogger::log_warning("Unable to find orderId({$orderId}) because it was soft deleted or doesnt exist in CRM", ['message' => $e->getMessage()]);
        }
    }

    /**
     * Determine is an order can be returned given business logic.
     * @return bool
     */
    public function canReturn(): bool
    {
        $isUnReturnableStatus      = ($this->isPending || $this->isDeclined);
        $isNotShippedWithInventory = ($this->isInventoryActive && !$this->isShipped);

        return $isUnReturnableStatus || !$isNotShippedWithInventory;
    }
}
