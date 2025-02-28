<?php


namespace App\Lib\LineItems\Messages;

/**
 * Class ForceBillMessage
 * @package App\Lib\LineItems\Messages
 */
class ForceBillMessage
{
    const SUCCESS_MESSAGE_TPL  = 'The order was billed successfully. Product #{product_id}{variant} for original order #{original_main_order_id} was archived and recurring was stopped.  New order ID#{new_order_id} was created{stop_message}';
    const ERROR_MESSAGE_TPL    = 'Failed to bill product #{product_id}{variant} for original order #{original_main_order_id}';
    const SUCCESS_STOP_MESSAGE = ', but any future recurring was stopped on that line item.';
    const CODE_INVALID_STATUS  = 380;
    const BILLING_ERROR        = 'Billing error: {billing_error}';

    /**
     * @var int $mainOrderId
     */
    protected int $mainOrderId;

    /**
     * @var int $newOrderId
     */
    protected int $newOrderId;

    /**
     * @var int $productId
     */
    protected int $productId;

    /**
     * @var int $variantId
     */
    protected int $variantId;

    /**
     * ForceBillMessage constructor.
     * @param $productId
     * @param $newOrderId
     * @param $mainOrderId
     * @param int $variantId
     */
    public function __construct($productId, $newOrderId, $mainOrderId, $variantId = 0)
    {
        $this->productId   = $productId;
        $this->newOrderId  = $newOrderId;
        $this->mainOrderId = $mainOrderId;
        $this->variantId   = $variantId;
    }

    /**
     * Fetch the success message.
     * @param bool $stop
     * @return string
     */
    public function success($stop = true): string
    {
        return strtr(self::SUCCESS_MESSAGE_TPL, [
            '{product_id}'             => $this->productId,
            '{variant}'                => $this->variantId ? " (Variant #{$this->variantId})" : '',
            '{original_main_order_id}' => $this->mainOrderId,
            '{new_order_id}'           => $this->newOrderLink(),
            '{stop_message}'           => ($stop ? self::SUCCESS_STOP_MESSAGE : '.')
        ]);
    }

    /**
     * Billing error message from internal process
     * @param string $message
     * @return string
     */
    public function billingError(string $message): string
    {
        return strtr(self::BILLING_ERROR, ['{billing_error}' => $message]);
    }

    /**
     * The default error message.
     * @return string
     */
    public function defaultError(): string
    {
        return strtr(self::ERROR_MESSAGE_TPL, [
            '{product_id}'             => $this->productId,
            '{variant}'                => $this->variantId ? " (Variant #{$this->variantId})" : '',
            '{original_main_order_id}' => $this->mainOrderId,
        ]);
    }

    /**
     * Fetch the new order URL.
     * @return string
     */
    public function newOrderLink(): string
    {
        return "<span style='font-size:16px' class='fakeLink' onclick='Redirect(\"orders.php?show_details=show_details&show_folder=view_all&fromPost=1&act=&page=0&show_by_id={$this->newOrderId}\")'>{$this->newOrderId}</span>";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->success();
    }
}
