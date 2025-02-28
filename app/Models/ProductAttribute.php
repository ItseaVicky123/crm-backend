<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class ProductAttribute
 * @package App\Models
 */
class ProductAttribute extends BaseModel
{

    use LimeSoftDeletes;

    const UPDATED_AT = false;
    const DELETED_FLAG = 'deleted';

    /**
     * @var string
     */
    protected $table = 'product_attribute';

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
        'options',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'product_id',
        'parent_id',
        'order_key',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'options',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'is_deleted' => self::DELETED_FLAG,
    ];

    public static function boot()
    {
        parent::boot();

        self::deleted(function($attribute) {
            if (! $attribute->parent_id) {
                $orderKey = 1;

                self::where('product_id', $attribute->product_id)
                    ->where('parent_id', 0)
                    ->get()
                    ->each(function($sibling_parent) use (&$orderKey) {
                        $sibling_parent->update([
                            'order_key' => $orderKey,
                        ]);
                        $orderKey++;
                    });
            } else {
                $orderKey = 1;

                self::where('product_id', $attribute->product_id)
                    ->where('parent_id', $attribute->parent_id)
                    ->get()
                    ->each(function($sibling_child) use (&$orderKey) {
                        $sibling_child->update([
                            'order_key' => $orderKey,
                        ]);
                        $orderKey++;
                    });

                $parent = $attribute->parent;

                if ($parent && !$parent->attributes()->count()) {
                    $parent->delete();
                }
            }
        });

        self::deleting(function($attribute) {
            if (! $attribute->parent_id) {
                $attribute->attributes()->get()->each(function($child_attribute) {
                    $child_attribute->delete();
                });
            } else {
                $parent = $attribute->parent;

                if ($parent && $parent->attributes()->count() > 1) {
                    $deleteVariant = true;
                } else {
                    $deleteVariant = $attribute->product()->first()->attributes()->count() == 1;
                }

                if ($deleteVariant) {
                    $attribute->variants->each(function($variant_attribute) {
                        if ($variant = $variant_attribute->variant()->first()) {
                            $variant->delete();
                        }
                    });
                } else {
                    $attribute->variants->each(function($variant_attribute) {
                        $variant_attribute->delete();
                    });
                }
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'attribute_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptionsAttribute()
    {
        return $this->attributes()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object
     */
    public function option()
    {
        return $this->attributes();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariantsAttribute()
    {
        return $this->variants()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getParentAttribute()
    {
        return $this->parent()->first();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($this->attributes['parent_id']) {
            $this->makeHidden('options');
        }

        return parent::toArray();
    }
}
