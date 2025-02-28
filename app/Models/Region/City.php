<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Models\Country as LegacyCountry;

class City extends Model
{
    use Eloquence, Mappable;

    protected $table = 'all_clients_limelight.region_city';

    protected $hidden = [
        'date_in',
        'update_in',
        'created_id',
        'updated_id',
        'place_fips_alpha_2',
        'city',
        'country_id',
        'place_id',
    ];

    protected $maps = [
        'name'              => 'city',
        'legacy_country_id' => 'country_id',
        'state_id'          => 'place_id',
        'created_at'        => 'date_in',
        'updated_at'        => 'update_in',
    ];

    protected $appends = [
        'name',
        'legacy_country_id',
        'state_id',
        'created_at',
        'updated_at',
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'place_id');
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function legacy_country()
    {
        return $this->belongsTo(LegacyCountry::class, 'country_id', 'countries_id');
    }
}
