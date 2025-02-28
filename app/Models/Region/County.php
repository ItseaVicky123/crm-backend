<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Models\Country as LegacyCountry;

class County extends Model
{
    use Eloquence, Mappable;

    protected $table = 'all_clients_limelight.region_county';

    protected $hidden = [
        'date_in',
        'update_in',
        'updated_id',
        'created_id',
        'county',
        'place_id',
        'country_id',
        'country_code',
        'place_fips_alpha_2',
    ];

    protected $maps = [
        'name'              => 'county',
        'legacy_country_id' => 'country_id',
        'state_id'          => 'place_id',
        'created_at'        => 'date_in',
        'updated_at'        => 'update_in',
    ];

    protected $appends = [
        'name',
        'state_id',
        'legacy_country_id',
        'created_at',
        'updated_at',
    ];

    protected function legacy_country()
    {
        return $this->belongsTo(LegacyCountry::class, 'country_id', 'countries_id');
    }

    protected function state()
    {
        return $this->belongsTo(State::class, 'place_id');
    }
}
