<?php

namespace App\Models;

use App\Traits\HasImmutable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class CouponType
 * Reader for the v_coupon_types view, uses slave connection.
 * @package App\Models
 */
class CouponType extends Model
{
    use HasImmutable, ModelImmutable;

    const ORDER        = 1;
    const PRODUCT      = 2;
    const IS_IMMUTABLE = true;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_coupon_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
