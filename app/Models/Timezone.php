<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class Timezone
 * Reader for the v_time_zone_name view, uses slave connection.
 * @package App\Models
 */
class Timezone extends BaseModel
{
   use ModelImmutable;

   protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

   const SYS_DEFAULT = 'America/New_York';

   protected $table = 'v_time_zone_name';

   public function getFormattedNameAttribute()
   {
      return strtr($this->name, [
         '/' => ' - ',
         '_' => ' ',
      ]);
   }
}
