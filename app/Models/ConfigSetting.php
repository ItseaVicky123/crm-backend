<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ConfigSetting extends Model
{
   use Eloquence, Mappable;

   const CREATED_AT = 'createdOn';
   const UPDATED_AT = 'updatedOn';

   protected $primaryKey = 'configSettingsId';

   public const ENABLE_BLACKLIST_V2_SETTING = 'ENABLE_BLACKLIST_V2';

   protected $visible = [
       'id',
       'name',
       'description',
       'key',
       'value',
   ];
   protected $maps = [
       'id' => 'configSettingsId',
   ];
   protected $appends = [
       'id',
   ];

   public function scopeKey(Builder $query, $key)
   {
       return $query->where('key', $key);
   }
}
