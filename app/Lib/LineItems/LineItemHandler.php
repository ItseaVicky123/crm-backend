<?php


namespace App\Lib\LineItems;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Upsell;
use App\Models\DeclinedCC;
use App\Lib\LineItems\Interfaces\LineItemActionInterface;
use App\Lib\LineItems\Contracts\LineItemHandlerContract;

/**
 * Class LineItemHandler
 * @package App\Lib\LineItems
 */
class LineItemHandler implements LineItemActionInterface
{
    /**
     * @var int $orderId
     */
    protected int $orderId;

    /**
     * @var int $orderTypeId
     */
    protected int $orderTypeId;

    /**
     * @var Model|null $orderModel
     */
    protected ?Model $orderModel = null;

    /**
     * LineItemHandler constructor.
     * @param LineItemHandlerContract $contract
     * @throws \Exception
     */
    public function __construct(LineItemHandlerContract $contract)
    {
        if ($contract->hasModel()) {
            $this->orderModel  = $contract->getOrderModel();
            $this->orderId     = $this->orderModel->id;
            $this->orderTypeId = $this->orderModel->getOrderTypeId();
        } else {
            $this->orderId     = $contract->getOrderId();
            $this->orderTypeId = $contract->getOrderTypeId();
            $this->initializeModel();
        }
    }

    /**
     * Reset recurring on a line item.
     * @throws \Exception
     * @return bool
     */
    public function resetRecurring(): bool
    {
        $success = false;

        // Do the reset recurring action
        //
        if ($this->orderModel) {
            if (! $this->orderModel->is_recurring && $this->orderModel->is_hold) {
                $isMainOrder = $this->orderTypeId === ORDER_TYPE_MAIN;
                $mainOrderId = $this->orderId;

                // If it is an upsell then get the main order ID from main_orders_id
                //
                if (! $isMainOrder) {
                    $mainOrderId = $this->orderModel->main_orders_id;
                }

                // Update the line item statuses
                //
                $success = $this->orderModel->update([
                    'is_recurring' => 1,
                    'is_hold'      => 0,
                    'hold_at'      => null,
                ]);

                // Reset hold type since it's not on hold anymore
                $this->orderModel->order_product->update(['hold_type_id' => null]);

                // Delete records from declined_ccs so that the natural recurring cron will pick these up again
                //
                DeclinedCC::where([
                    ['orders_id',  $this->orderId],
                    ['is_order_or_upsell', (int) (! $isMainOrder)]
                ])->delete();

                // Add history
                //
                $this->orderModel->addHistoryNote(
                    $isMainOrder ? 'reset-recurring' : 'recurring-upsell-reset',
                    $this->orderModel->subscription_id
                );

                if (\App\Models\ValueAddService::isEnabled(\value_add_service_entry::BIGCOMMERCE)) {
                    \Illuminate\Support\Facades\Event::dispatch(new \App\Events\Subscription\SubscriptionResumed($this->orderModel));
                }

                // Call legacy common provider update
                //
                \commonProviderUpdateOrder($mainOrderId, 'reset_recurring');
            }
        } else {
            throw new \Exception('Order model not defined');
        }

        return $success;
    }

    /**
     * @throws \Exception
     */
    private function initializeModel(): void
    {
        if ($this->orderId && $this->orderTypeId && !$this->orderModel) {
            switch ($this->orderTypeId) {
                case ORDER_TYPE_MAIN:
                    $this->orderModel = Order::findOrFail($this->orderId);
                    break;
                case ORDER_TYPE_UPSELL:
                    $this->orderModel = Upsell::findOrFail($this->orderId);
                    break;
                default:
                    throw new \Exception("Invalid order type {$this->orderModel}");
            }
        }
    }
}
