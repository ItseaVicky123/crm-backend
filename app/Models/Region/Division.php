<?php

namespace App\Models\Region;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Division extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    protected $table = 'all_clients_limelight.region_division';

    protected $hidden = [
        'date_in',
        'update_in',
    ];

    protected $maps = [
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
    ];

    protected $appends = [
        'created_at',
        'updated_at',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
