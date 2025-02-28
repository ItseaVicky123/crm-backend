<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class ProductCategory
 * @package App\Models
 */
class ProductCategory extends Model
{
    use Eloquence, Mappable;

   /**
    * @var string
    */
    protected $table = 'products_to_categories';

   /**
    * @var array
    */
    protected $primaryKey = [
       'products_id',
       'categories_id',
    ];

   /**
    * @var array
    */
    protected $maps = [
        'product_id'  => 'products_id',
        'category_id' => 'categories_id',
    ];

   /**
    * @var array
    */
   protected $visible = [
      'product_id',
      'category_id',
   ];

   /**
    * @var array
    */
   protected $appends = [
      'product_id',
      'category_id',
   ];

   /**
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id', 'products_id');
    }

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
    public function category()
    {
       return $this->hasOne(Category::class, 'categories_id', 'categories_id');
    }
}
