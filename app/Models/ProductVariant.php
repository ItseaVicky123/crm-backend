<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class ProductVariant
 * @package App\Models
 *
 * @property int $id
 * @property string|null $price
 * @property int $quantity
 *
 * @property-read Product $product
 *
 * @method static ProductVariant find(int $id)
 */
class ProductVariant extends BaseModel
{
    use LimeSoftDeletes;

    const ENTITY_ID  = 14;

    /**
     * @var string
     */
    protected $table = 'product_variant';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'price',
        'quantity',
        'weight',
        'sku_num',
        'attributes',
        'inventory',
        'inventories'
    ];

    /**
     * @var array
     */
    protected $appends = [
        'attributes',
        'default_image',
        'default_email_image',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'product_id',
        'price',
        'weight',
        'quantity',
        'sku_num',
        'order_key',
        'subscription_variant_id',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($variant) {
            $variant->price    = $variant->price ?? $variant->product->price;
            $variant->quantity = $variant->quantity ?? $variant->product->quantity;
        });

        self::created(function ($variant) {
            $variant->update([
                'subscription_variant_id' => $variant->id,
            ]);
        });

        self::deleting(function ($variant) {
            $variant->attributes()->get()->each(function ($variant_attribute) {
                $variant_attribute->delete();
            });
        });
    }

    /**
     * @param Builder $query
     * @param $productId
     * @return Builder
     */
    public function scopeForProduct(Builder $query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productVariants()
    {
        return $this->belongsTo(Product::class, 'products_id', 'product_id');
    }

    public function getProductNameAttribute()
    {
        return $this->product->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'variant_id', 'id');
    }

    public function getAttributesAttribute()
    {
        return $this->attributes()->get()->makeHidden(['variant_id', 'variant']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function images()
    {
        return $this->belongsToMany(Image::class, 'image_product', 'product_id', 'image_id')
            ->withPivot('is_default', 'is_default_email')
            ->where('entity_type_id', self::ENTITY_ID);
    }

    /**
     * @return Image|null
     */
    public function getDefaultImageAttribute()
    {
        return $this->images()
            ->wherePivot('is_default', 1)
            ->first();
    }

    /**
     * @return Image|null
     */
    public function getDefaultEmailImageAttribute()
    {
        return $this->images()
            ->wherePivot('is_default_email', 1)
            ->first() ?? $this->default_image;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inventory()
    {
        return $this->hasOne(ProductInventory::class, 'product_variant_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function inventories()
    {
        return $this->hasMany(ProductInventory::class, 'product_variant_id', 'id');
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        if (is_null($this->price)) {
            return $this->product->price;
        } else {
            return $this->price;
        }
    }

    /**
     * This will replace the relevant null values of the variant with the product values
     * add more as needed
     *
     */
    public function getVariantWithValues(): ProductVariant
    {
        if (! $this->price) {
            $this->price = $this->product->price;
        }

        if (! $this->quantity) {
            $this->quantity = $this->product->max_quantity;
        }

        return $this;
    }
}
