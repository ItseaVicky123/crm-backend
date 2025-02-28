<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class OrderLinkType
 * Reader for the v_order_link_types view, uses slave connection.
 * @package App\Models
 */
class OrderLinkType extends Model
{
    use ModelImmutable;

    const RECURRING = 1;
    const COMBINE_ADDRESS = 2;
    const ACCOUNT_UPDATER = 3;
    const UPSELL = 4;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_order_link_types';


    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
