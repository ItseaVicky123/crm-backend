<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'active',
        'products',
        'system',
        'description',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'active',
        'products',
        'system',
        'description',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'products' => 'array',
    ];

    public static function boot()
    {
        static::addGlobalScope(new ActiveScope());
    }
}
