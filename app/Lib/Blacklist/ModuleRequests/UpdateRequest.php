<?php

namespace App\Lib\Blacklist\ModuleRequests;

use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasBlacklist;

/**
 * Class UpdateRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class UpdateRequest extends SaveRequest
{
    use HasBlacklist;

    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = false;

    /**
     * UpdateRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setBlacklist($this->id);
    }
}
