<?php

namespace App\Models\Region;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Zipcode extends Model
{
   use Eloquence, Mappable, LimeSoftDeletes;

   protected $table = 'all_clients_limelight.region_zipcode';
}