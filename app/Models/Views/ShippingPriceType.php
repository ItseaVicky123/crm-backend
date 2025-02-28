<?php

namespace App\Models\Views;

use App\Models\ReadOnlyModel;
use App\Traits\ModelImmutable;

/**
 * Class ShippingPriceType
 * Reader for the v_shipping_price_types view, uses slave connection.
 * @package App\Models\Views
 */
class ShippingPriceType extends ReadOnlyModel
{
    use ModelImmutable;

    const DEFAULT      = 1;
    const INITIAL      = 2;
    const SUBSCRIPTION = 3;
    const CUSTOM       = 4;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    public $table = 'v_shipping_price_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'active',
    ];
}
