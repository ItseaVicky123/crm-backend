<?php

namespace App\Lib\Blacklist\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasBlacklist;

/**
 * Class BlacklistDetailRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class BlacklistDetailRequest extends ModuleRequest
{
    use HasBlacklist;

    /**
     * BlacklistDetailRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->validate([
            'id' => 'required|int|exists:mysql_slave.blacklist_rule_details,id',
        ], [
            'id' => 'Blacklist Rule Detail ID',
        ]);

        $this->setBlacklistDetail($this->id);
    }
}
