<?php

namespace App\Lib\Blacklist\ModuleRequests;

use Illuminate\Validation\ValidationException;

/**
 * Class UpdateRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class UpdateRuleDetailRequest extends SaveRuleDetailRequest
{
    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = false;

    /**
     * UpdateRuleDetailRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $id = ($data['id'] ?? null);
        $this->setBlacklistDetail($id);
    }
}
