<?php

namespace App\Models\Region;

use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Country as LegacyCountry;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Country extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    protected $table = 'all_clients_limelight.region_country';

    protected $hidden = [
        'date_in',
        'update_in',
        'fips_alpha',
        'sales_tax',
        'use_tax',
        'll_country_id',
    ];

    protected $maps = [
        'name'       => 'fips_alpha',
        'legacy_id'  => 'll_country_id',
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
    ];

    protected $appends = [
        'name',
        'legacy_id',
        'created_at',
        'updated_at',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function cities()
    {
        return $this->hasManyThrough(City::class, State::class, 'country_id', 'place_id');
    }

    public function legacyCountry()
    {
        return $this->belongsTo(LegacyCountry::class, 'll_country_id', 'countries_id');
    }

    public function primary_division()
    {
        return $this->belongsTo(Division::class, 'primary_division_id');
    }
}
