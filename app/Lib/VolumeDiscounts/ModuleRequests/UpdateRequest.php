<?php

namespace App\Lib\VolumeDiscounts\ModuleRequests;

use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasVolumeDiscount;

/**
 * Class UpdateRequest
 * @package App\Lib\VolumeDiscounts\ModuleRequests
 */
class UpdateRequest extends SaveRequest
{
    use HasVolumeDiscount;

    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = false;

    /**
     * UpdateRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setVolumeDiscount($this->id);
    }
}
