<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sofa\Eloquence\Eloquence;

/**
 * Class CouponProduct
 * @package App\Models
 */
class CouponProduct extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'coupon_product';

    /**
     * @var string[]
     */
    protected $hidden = [];

    /**
     * @var array
     */
    protected $fillable = [
        'coupon_id',
        'product_id',
        'variant_id',
        'is_eligible',
    ];

    protected $visible = [
        'coupon_id',
        'product_id',
        'variant_id',
        'is_eligible',
        'product',
    ];

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'products_id', 'product_id');
    }
}
