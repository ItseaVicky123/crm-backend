<?php

namespace App\Models;

use App\Traits\HasImmutable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class CouponDiscountType
 * Reader for the v_coupon_discount_types view, uses slave connection.
 * @package App\Models
 */
class CouponDiscountType extends Model
{
    use HasImmutable, ModelImmutable;

    const FLAT         = 33;
    const PERCENT      = 34;
    const IS_IMMUTABLE = true;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_coupon_discount_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
