<?php

namespace App\Lib\Blacklist\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Blacklist\BlacklistRule;
use Illuminate\Validation\ValidationException;

/**
 * Class SaveRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class SaveRequest extends ModuleRequest
{
    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = true;

    /**
     * SaveRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $rules        = [
            'name'        => "required|max:255",
            'description' => "required",
            'status'      => 'bool',
        ];
        $attributes   = [
            'name'        => 'Blacklist Rule Name',
            'description' => 'Blacklist Rule Description',
            'status'      => 'Active Flag',
        ];

        if (! $this->isCreate) {
            $rules['id']      = 'required|int|exists:mysql_slave.blacklist_rules,id';
            $attributes['id'] = 'Blacklist Rule ID';
        }

        $this->validate($rules, $attributes);
    }
}
