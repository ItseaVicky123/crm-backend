<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ProductBundleChild
 * @package App\Models
 */
class ProductBundleChild extends Model
{
    use Eloquence, Mappable, HasCompositePrimaryKey;

    public $table = 'product_bundle';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $primaryKey = [
        'bundle_product_id',
        'child_product_id',
    ];

    /**
     * @var array
     */
    public $visible = [
        'product_id',
        'quantity',
        'name',
        'id',
    ];

    /**
     * @var array
     */
    public $maps = [
        'id'         => 'child_product_id',
        'product_id' => 'child_product_id',
    ];

    /**
     * @var array
     */
    public $appends = [
        'id',
        'product_id',
        'name',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'quantity',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'products_id', 'child_product_id');
    }

    /**
     * @return string
     */
    public function getNameAttribute()
    {
        if ($product = $this->product) {
            return $product->name;
        }

        return '';
    }
}
