<?php

namespace App\Models\Region;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class State extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    protected $table = 'all_clients_limelight.region_place';

    protected $hidden = [
        'sales_tax',
        'use_tax',
        'avg_local_tax',
        'sales_tax_effective',
        'capital',
        'state_site_link',
        'state_local_tax_link',
        'state_local_tax_link',
        'fips_alpha',
        'date_in',
        'update_in',
    ];

    protected $maps = [
        'name'       => 'fips_alpha',
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
        'code'       => 'fips_alpha2',
    ];

    protected $appends = [
        'name',
        'created_at',
        'updated_at',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
