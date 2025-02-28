<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProductShippingRestriction
 * @package App\Models
 */
class ProductShippingRestriction extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $attributes = [
        'country_id' => Country::UNITED_STATES_ID,
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'country_id',
        'value',
        'created_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'country_id',
        'value',
        'created_by',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function($restriction) {
            $restriction->created_by = \current_user(User::SYSTEM);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function country()
    {
        return $this->hasOne(Country::class, 'countries_id', 'country_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getCountryAttribute()
    {
        return $this->country()->first();
    }
}
