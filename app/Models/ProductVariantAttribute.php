<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Builder;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProductVariantAttribute
 * @package App\Models
 */
class ProductVariantAttribute extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'product_variant_attribute_jct';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'attribute',
        'variant',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'attribute',
        'variant',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'attribute_id',
        'variant_id',
    ];

    public static function boot()
    {
        parent::boot();

        // Only include relation for non-deleted
        static::addGlobalScope('has_active', function (Builder $builder) {
            $builder->whereHas('attribute');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attribute()
    {
        return $this->hasOne(ProductAttribute::class, 'id', 'attribute_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variant()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'variant_id');
    }

    /**
     * @return array
     */
    public function getAttributeAttribute()
    {
        if ($child = $this->attribute()->first()) {
            if ($parent = $child->parent()->first()) {
                return [
                    'id'     => $parent->id,
                    'name'   => $parent->name,
                    'option' => [
                        'id'   => $child->id,
                        'name' => $child->name,
                    ]
                ];
            }
        }

        return [];
    }
}
