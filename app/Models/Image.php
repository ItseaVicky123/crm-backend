<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\HasUuidFind;

class Image extends Model
{
    use SoftDeletes, HasUuidFind;

    protected $visible = [
        'id',
        'uuid',
        'path',
        'alias',
        'created_at',
        'updated_at',
        'is_default',
        'is_default_email',
    ];

    protected $appends = [
        'path',
        'is_default',
        'is_default_email',
    ];

    protected $guarded = [
        'id',
        'uuid',
    ];

    const GATEWAY_BRAND_IMG = 'gateway_brand_image';

    // Define all image cropper fields in this array.
    public static $cropperFields = [
        self::GATEWAY_BRAND_IMG => 'gateway brand image',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($image) {
            $image->uuid = Str::uuid();
        });

        static::deleting(function ($image) {
            if (!empty($image->uri) && Storage::cloud()->exists($image->uri)) {
                Storage::cloud()->delete($image->uri);
            }
        });
    }

    /**
     * Define all file upload folders in an array.
     *
     * @return string
     */
    public static function fileFolders($fileType): string
    {
        $fileFolders = [
            self::GATEWAY_BRAND_IMG => '/providers_brands/',
        ];

        return $fileFolders[$fileType] ?: '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'image_product', 'image_id', 'product_id')
            ->withPivot('is_default', 'is_default_email')
            ->where('entity_type_id', Product::ENTITY_ID);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'image_product', 'image_id', 'product_id')
            ->withPivot('is_default', 'is_default_email')
            ->where('entity_type_id', ProductVariant::ENTITY_ID);
    }

    /**
     * @param Builder $query
     * @param $productId
     * @return Builder
     */
    public function scopeForProduct(Builder $query, $productId)
    {
        return $query->whereHas('products', function (Builder $query) use ($productId) {
            $query->where('id', $productId)->where('entity_type_id', Product::ENTITY_ID);
        });
    }

    /**
     * @param Builder $query
     * @param $variantId
     * @return Builder
     */
    public function scopeForVariant(Builder $query, $variantId)
    {
        return $query->whereHas('variants', function (Builder $query) use ($variantId) {
            $query->where('id', $variantId)->where('entity_type_id', ProductVariant::ENTITY_ID);
        });
    }

    /**
     * @return mixed
     */
    public function getPathAttribute()
    {
        return $this->is_hosted
            ? Storage::cloud()->url($this->uri)
            : $this->uri;
    }

    /**
     * @return int
     */
    public function getIsDefaultAttribute()
    {
        return $this->pivot->is_default ?? 0;
    }

    /**
     * @return int
     */
    public function getIsDefaultEmailAttribute()
    {
        return $this->pivot->is_default_email ?? 0;
    }
}
