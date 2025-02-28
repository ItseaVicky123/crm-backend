<?php

namespace App\Models\Campaign;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use product\bundle\meta as BundleMeta;
use App\Models\Product as ProductProfile;

/**
 * Class Product
 * @package App\Models\Campaign
 */
class Product extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'campaign_products';

    /**
     * @var array
     */
    protected $visible = [
        'product_id',
        'campaign_id',
        'is_upsell',
        'id',
        'name',
        'is_trial_allowed',
        'is_bundle',
        'is_custom_bundle',
        'children',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(ProductProfile::class, 'products_id', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }

    /**
     * @return mixed
     */
    public function getIdAttribute()
    {
        return $this->attributes['product_id'];
    }

    /**
     * @return string
     */
    public function getNameAttribute()
    {
        $this->setProduct();

        if (isset($this->product)) {
            return $this->product->name;
        }

        return '';
    }

    public function getIsTrialAllowedAttribute()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getIsBundleAttribute()
    {
        $this->setProduct();

        if (isset($this->product)) {
            return $this->product->bundle_type_id ? 1 : 0;
        }

        return 0;
    }

    /**
     * @return int
     */
    public function getIsCustomBundleAttribute()
    {
        $this->setProduct();

        if (isset($this->product)) {
            return $this->product->bundle_type_id == BundleMeta::BUNDLE_CUSTOM_BUILT ? 1 : 0;
        }

        return 0;
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

    protected function setProduct()
    {
        if (! isset($this->product)) {
            $this->product = $this->product()->first();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }
}
