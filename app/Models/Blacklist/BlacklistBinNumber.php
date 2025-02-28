<?php

namespace App\Models\Blacklist;

use App\Models\BaseModel;
/**
 * Class BlacklistBinNumber
 *
 * @package App\Models\BlacklistBinNumber
 */
class BlacklistBinNumber extends BaseModel
{
    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'rule_detail_id',
        'value',
    ];

}
