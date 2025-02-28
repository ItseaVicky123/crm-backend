<?php


namespace App\Models\Affiliates;

use App\Models\ReadOnlyModel;
use App\Traits\ModelImmutable;

/**
 * Affiliate type names mapped to IDs (AFFID, AFID, AID, etc)
 * Class AffiliateType
 * Reader for the v_affiliate_types view, uses slave connection.
 * @package App\Models\Affiliates
 */
class AffiliateType extends ReadOnlyModel
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    
    
    /**
     * @var string
     */
    public $table = 'v_affiliate_types';

    /**
     * @var bool
     */
    public $timestamps = false;
}
