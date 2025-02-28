<?php

namespace App\Lib\Traits;

use App\Models\VolumeDiscounts\VolumeDiscount;

/**
 * Class HasVolumeDiscount
 * @package App\Lib\Traits
 */
trait HasVolumeDiscount
{
    /**
     * @var VolumeDiscount|null $volumeDiscount
     */
    protected ?VolumeDiscount $volumeDiscount = null;

    /**
     * @return VolumeDiscount|null
     */
    public function getVolumeDiscount(): ?VolumeDiscount
    {
        return $this->volumeDiscount;
    }

    /**
     * Load the volume discount model.
     * @param int $id
     * @return $this
     */
    public function setVolumeDiscount(int $id): self
    {
        $this->volumeDiscount = VolumeDiscount::findOrFail($id);

        return $this;
    }


}