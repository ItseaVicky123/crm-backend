<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductPriceType
 * @package App\Models
 */
class ProductPriceType extends Model
{
    use ModelImmutable;

    const FIXED = 1;
    const PER_ITEM = 2;
    const PRODUCT = 3;

    /**
     * @var string
     */
    protected $table  = 'vlkp_product_price_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @return int
     */
    public static function getFixedType()
    {
        return self::FIXED;
    }

    /**
     * @return int
     */
    public static function getPerItemType()
    {
        return self::PER_ITEM;
    }

    /**
     * @return int
     */
    public static function getProductType()
    {
        return self::PRODUCT;
    }
}
