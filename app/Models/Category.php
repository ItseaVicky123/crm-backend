<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSequencer;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use App\Traits\HasImmutable;

/**
 * Class Category
 * @package App\Models
 */
class Category extends Model
{
    use Eloquence, Mappable, LimeSequencer, LimeSoftDeletes, HasCreator, HasImmutable;

    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'last_modified';
    const UPDATED_BY = 'update_id';

    /**
     * @var string
     */
    protected $primaryKey = 'categories_id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'description',
        'product_count',
        'is_active',
        'is_deleted',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'          => 'categories_id',
        'name'        => 'meta.name',
        'description' => 'meta.description',
        'created_at'  => 'date_added',
        'updated_at'  => 'last_modified',
        'is_active'   => 'active',
        'is_deleted'  => 'deleted',
        'created_by'  => 'creator.name',
        'updated_by'  => 'updator.name',
    ];

    /**
     * @var array
     */
    protected $fillable = [
       'id',
       'name',
       'description',
       'created_id',
       'update_id'
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;
    protected $created_id;
    protected $updated_id;
    protected $created_at;
    protected $updated_at;

    public static function boot()
    {
        parent::boot();

        static::updating(function ($category) {
            return $category->checkImmutable();
        });

        static::deleting(function ($category) {
            return $category->checkImmutable();
        });
    }

    public function meta()
    {
        return $this->hasOne(CategoryDescription::class, 'categories_id', 'categories_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_to_categories', 'categories_id', 'products_id');
    }

    public function getProductCountAttribute()
    {
        return $this->products->count();
    }
}
