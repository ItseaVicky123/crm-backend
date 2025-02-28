<?php


namespace App\Lib\TrialWorkflow;

use App\Models\Shipping;
use App\Lib\BillingModels\ShippingPriceCalculationInput;
use App\Lib\TrialWorkflow\OrderProcess\RebillHandler;

/**
 * Class NextShippingAmount
 * @package App\Lib\TrialWorkflow
 */
class NextShippingAmount
{
    /**
     * @var string|null $shippingMethodName
     */
    protected ?string $shippingMethodName = null;

    /**
     * @var int|null $shippingMethodId
     */
    protected ?int $shippingMethodId = null;

    /**
     * @var float|null $defaultPrice
     */
    protected ?float $defaultPrice = null;

    /**
     * @var Shipping|null
     */
    protected ?Shipping $shippingModel = null;

    /**
     * @var RebillHandler $handler
     */
    protected RebillHandler $handler;

    /**
     * NextShippingAmount constructor.
     * @param RebillHandler $handler
     * @param float|null $defaultPrice
     * @param string $shippingMethodName
     * @param int $shippingMethodId
     */
    public function __construct(RebillHandler $handler, ?float $defaultPrice = null, $shippingMethodName = '', $shippingMethodId = 0)
    {
        $this->defaultPrice       = $defaultPrice;
        $this->shippingMethodId   = $shippingMethodId;
        $this->shippingMethodName = $shippingMethodName;
        $this->handler            = $handler;

        if ($this->shippingMethodId) {
            $this->shippingModel = Shipping::findOrFail($this->shippingMethodId);
        } else if ($this->shippingMethodName) {
            $this->shippingModel = Shipping::where([
                ['s_identity', $this->shippingMethodName]
            ])->first();
        }
    }

    /**
     * Calculate the next shipping amount when using trial workflows
     * @return float
     */
    public function calculatedAmount(): float
    {
        $amount = $this->defaultPrice ?? 0;

        if ($this->shippingModel && $this->handler) {
            if ($unit = $this->handler->getNextUnit()) {
                $amount = $unit->getUnitShippingPrice(new ShippingPriceCalculationInput([
                    'defaultPrice'  => $amount,
                    'shippingModel' => $this->shippingModel,
                ]));
            }
        }

        return $amount;
    }
}
