<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;
use \App\Models\Product as BaseProduct;
use product\bundle\meta as BundleMeta;

/**
 * Class Product
 * @package App\Models\Offer
 */
class Product extends Model
{
    use HasCompositePrimaryKey;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'billing_offer_product';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'offer_id',
        'is_trial_allowed',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'is_trial_allowed',
        'is_bundle',
        'is_custom_bundle',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'is_bundle',
        'is_custom_bundle',
    ];

    /**
     * @var array
     */
    protected $primaryKey = [
        'product_id',
        'offer_id',
    ];

    protected $product;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(BaseProduct::class, 'products_id', 'product_id');
    }

    /**
     * @return mixed
     */
    protected function getIdAttribute()
    {
        return $this->attributes['product_id'];
    }

    /**
     * @return mixed|string
     */
    protected function getNameAttribute()
    {
        $this->setProduct();

        return ($this->product ? $this->product->name : '');
    }

    /**
     * @return int
     */
    protected function getIsBundleAttribute()
    {
        $this->setProduct();

        return ($this->product && $this->product->bundle_type_id ? 1 : 0);
    }

    /**
     * @return int
     */
    protected function getIsCustomBundleAttribute()
    {
        $this->setProduct();

        return ($this->product && $this->product->bundle_type_id == BundleMeta::BUNDLE_CUSTOM_BUILT ? 1 : 0);
    }

    /**
     * @return array
     */
    public function getChildrenAttribute()
    {
        if ($this->getIsBundleAttribute()) {
            $children = $this
                ->product
                ->bundle_children
                ->toArray();

            foreach ($children as $child) {
                $child['id'] = $child['product_id'];

                unset($child['product_id']);
            }

            return $children;
        }

        return [];
    }

    private function setProduct()
    {
        if (! isset($this->product)) {
            $this->product = $this->product()->first();
        }
    }
}
