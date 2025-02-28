<?php

namespace App\Lib\VolumeDiscounts\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasVolumeDiscount;

/**
 * Class VolumeDiscountRequest
 * @package App\Lib\VolumeDiscounts\ModuleRequests
 */
class VolumeDiscountRequest extends ModuleRequest
{
    use HasVolumeDiscount;

    /**
     * VolumeDiscountRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->validate([
            'id' => 'required|int|exists:mysql_slave.volume_discounts,id',
        ], [
            'id' => 'Volume Discount ID',
        ]);
        $this->setVolumeDiscount($this->id);
    }
}