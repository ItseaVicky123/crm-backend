<?php

namespace App\Models;

use App\Traits\HasImmutable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class CouponDiscountBehaviorType
 * Reader for the v_coupon_discount_behavior_types view, uses slave connection.
 * @package App\Models
 */
class CouponDiscountBehaviorType extends Model
{
    use HasImmutable, ModelImmutable;

    const PRODUCT      = 36;
    const TOTAL        = 37;
    const SHIPPING     = 38;
    const IS_IMMUTABLE = true;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_coupon_discount_behavior_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
