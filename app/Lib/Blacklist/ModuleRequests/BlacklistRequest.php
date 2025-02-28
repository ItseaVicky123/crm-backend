<?php

namespace App\Lib\Blacklist\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasBlacklist;

/**
 * Class BlacklistRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class BlacklistRequest extends ModuleRequest
{
    use HasBlacklist;

    /**
     * BlacklistRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->validate([
            'id' => 'required|int|exists:mysql_slave.blacklist_rules,id',
        ], [
            'id' => 'Blacklist Rule ID',
        ]);

        $this->setBlacklist($this->id);
    }
}
