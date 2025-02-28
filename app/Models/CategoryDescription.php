<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class CategoryDescription extends Model
{
    use Eloquence, Mappable;

    protected $table = 'categories_description';
    protected $primaryKey = 'categories_id';
    protected $maps = [
        'category_id' => 'categories_id',
        'name'        => 'categories_name',
        'description' => 'categories_description',
    ];
    protected $fillable = ['category_id', 'name', 'description'];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::updating(function ($categoryDescription) {
            return $categoryDescription->category->checkImmutable();
        });

        static::deleting(function ($categoryDescription) {
            return $categoryDescription->category->checkImmutable();
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'categories_id', 'categories_id');
    }
}
