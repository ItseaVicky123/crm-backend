<?php

namespace App\Lib\Contexts\Events;


/**
 * Class CancelShipmentContextTrait
 * @package App\Lib\Contexts\Events
 */
trait CancelShipmentContextTrait
{
    /**
     * @var bool $isCancelShipmentActive
     */
    protected bool $isCancelShipmentActive = true;

    /**
     * Determine whether cancel shipment should happen in this context or not
     * @return bool
     */
    public function shouldDoCancelShipment(): bool
    {
        return $this->isCancelShipmentActive;
    }

    /**
     * @param bool $isCancelShipmentActive
     * @return $this
     */
    public function setIsCancelShipmentActive(bool $isCancelShipmentActive): self
    {
        $this->isCancelShipmentActive = $isCancelShipmentActive;

        return $this;
    }
}
