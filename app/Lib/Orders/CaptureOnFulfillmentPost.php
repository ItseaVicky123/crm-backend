<?php

namespace App\Lib\Orders;

use alt_pay_providers;
use App\Models\GatewayField;
use App\Models\OrderHistoryNote;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use App\Events\Order\Captured;
use App\Models\Order;
use App\Models\OrderStatus;
use gateway_adjuster;
use gatewayClass;
use orders;
use Illuminate\Support\Facades\DB;

/**
 * Class CaptureOnFulfillmentPost
 *
 * @package App\Lib\Orders
 */
class CaptureOnFulfillmentPost
{
    /**
     * @var int $orderId
     */
    protected int $orderId;

    /**
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = (int) $orderId;
    }

    /**
     * Determine if this order's capture on fulfillment post enabled
     *
     * @param bool $checkIfValidToCapture validate whether this order can be captured
     * @return bool
     */
    public function isEnabled(bool $checkIfValidToCapture = false): bool
    {
        return Order::withoutGlobalScopes()
            ->whereOrdersId($this->orderId)
            ->whereNotIn('payment_method', alt_pay_providers::$alt_pay_systems)
            ->when($checkIfValidToCapture, function ($q) {
                $q->where('orders_status', OrderStatus::STATUS_PENDING)
                    ->where('hasBeenPosted', false);
            })
            ->whereHas('gateway', fn($q) => $q->whereHas('fields', function ($q) {
                $q->where('fieldName', GatewayField::CAPTURE_ON_FULFILLMENT_POST)
                    ->where('fieldValue', 'yes');
            }))
            ->whereExists(function ($q) {
                // By some weird reason where(function($q){}) didn't want to work with whereHas(), so using whereExists
                $q->select(DB::raw('IFNULL(p.products_id, IFNULL(up.products_id, 0))'))
                    ->from('orders_products AS op')
                    ->whereColumn('op.orders_id', 'orders.orders_id')
                    ->leftJoin('products AS p', 'p.products_id', 'op.products_id')
                    ->leftJoin('upsell_orders AS uo', 'uo.main_orders_id', 'op.orders_id')
                    ->leftJoin('upsell_orders_products AS uop', 'uop.upsell_orders_id', 'uo.upsell_orders_id')
                    ->leftJoin('products AS up', 'up.products_id', 'uop.products_id')
                    // By some weird reason where(function($q){}) didn't want to work inside whereExists(function($q){})
                    ->whereRaw("(p.is_shippable = 1 OR up.is_shippable = 1)")
                    ->limit(1);
            })
            ->exists();
    }

    /**
     * Determine if this order is valid for capture
     *
     * @return bool
     */
    public function isValidForCapture(): bool
    {
        return $this->isEnabled(true);
    }

    /**
     * Returns null if not valid for capture OR new order's status after the capture was performed
     *
     * @param string $additionalNoteMessage
     * @return bool|null
     */
    public function captureOrderIfValid(string $additionalNoteMessage = ''): ?bool
    {
        // Not valid for capture, ignore this order
        if (! $this->isValidForCapture()) {
            return null;
        }

        $successfulCapture = false;
        $gatewayClass      = new gatewayClass([
            'newOrderId'         => $this->orderId,
            'performCaptureOnly' => true,
        ]);

        $gatewayClass->reroute_capture_method = true;

        $gatewayResponse      = $gatewayClass->performCapture();
        $orderObj             = new orders($this->orderId);
        $orderObj->campaignId = $orderObj->campaignOrderId;
        $orderResponse        = $orderObj->processOrderResponse($gatewayResponse);
        $gateway_adjuster     = new gateway_adjuster($this->orderId);
        $orderStatus          = $orderResponse;
        $strOrderStatus       = ucfirst(OrderStatus::STR_STATUS_MAPPING[$orderStatus]);

        if ($orderStatus === OrderStatus::STATUS_APPROVED) {
            $gateway_adjuster->update_camp_rebill_limit();
            $successfulCapture = true;
        } else {
            $gateway_adjuster->update_amounts(gateway_adjuster::ACTION_REVERT_TOTALS);
            \fileLogger::log_error(__METHOD__ . " - Order #{$this->orderId} capture has failed {$additionalNoteMessage} with a status {$orderResponse}. Error: {$gatewayResponse['errorMessage']}");
        }

        reset_recurring_info($this->orderId, $orderStatus);

        if ($orderStatus === OrderStatus::STATUS_APPROVED) {
            \tax_provider::commitOrder($this->orderId, $orderObj->campaignOrderId, $orderObj->customersId);
        }

        // we need to do the execute postback url when it attempts to captures
        Event::dispatch(new Captured($this->orderId));

        OrderHistoryNote::create([
            'order_id'  => $this->orderId,
            'message'   => "Order was marked as {$strOrderStatus} right before the fulfillment post" . $additionalNoteMessage,
            'type_name' => 'history-note-capture-on-fulfillment-post',
            'author'    => User::SYSTEM,
        ]);

        return $successfulCapture;
    }
}
