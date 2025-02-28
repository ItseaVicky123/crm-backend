<?php


namespace App\Lib\BillingModels;

use Illuminate\Support\Collection;
use App\Models\Shipping;

/**
 * Class ShippingPriceCalculationInput
 * @package App\Lib\BillingModels
 */
class ShippingPriceCalculationInput extends Collection
{
    /**
     * Default price passed in.
     * @var float|null $defaultPrice
     */
    protected ?float $defaultPrice = null;

    /**
     * Use the default price if this is true.
     * @var bool $isCustomOverride
     */
    protected bool $isCustomOverride = false;

    /**
     * Shipping method instance.
     * @var Shipping|null $shippingModel
     */
    protected ?Shipping $shippingModel = null;

    /**
     * ShippingPriceCalculationInput constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if ($this->has('defaultPrice')) {
            $this->defaultPrice = $this->get('defaultPrice');
        }

        if ($this->has('isCustomOverride')) {
            $this->isCustomOverride = (bool) $this->get('isCustomOverride');
        }

        if ($this->has('shippingModel')) {
            $this->shippingModel = $this->get('shippingModel');
        }
    }

    /**
     * Get initial amount from shipping method profile.
     * @return float|null
     */
    public function getInitialAmount(): ?float
    {
        return $this->shippingModel ? $this->shippingModel->amount_trial : 0;
    }

    /**
     * Get subscription amount from shipping method profile.
     * @return float|null
     */
    public function getSubscriptionAmount(): ?float
    {
        return $this->shippingModel ? $this->shippingModel->amount_recurring : 0;
    }

    /**
     * @return float|null
     */
    public function getDefaultPrice(): ?float
    {
        return $this->defaultPrice;
    }

    /**
     * @return bool
     */
    public function isCustomOverride(): bool
    {
        return $this->isCustomOverride;
    }
}