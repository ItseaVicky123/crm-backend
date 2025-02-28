<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class ProductDescription
 * @package App\Models
 */
class ProductDescription extends Model
{
    // use Eloquence, Mappable;
    use Mappable;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'products_description';

    /**
     * @var string
     */
    protected $primaryKey = 'products_id';

    /**
     * @var array
     */
    protected $maps = [
        'name'        => 'products_name',
        'description' => 'products_description',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'description',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'language_id'     => 1,
        'products_url'    => '',
        'products_viewed' => 0,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id', 'products_id');
    }
}
