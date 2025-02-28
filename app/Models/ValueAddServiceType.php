<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ValueAddServiceType extends Model
{
   use Mappable;
   use Eloquence;

   const DEMANDWARE_ID      = 23;
   const SMART_RETRIES_ID   = 26;
   const BIGCOMMERCE_ID     = 28;


    /**
     * @var string
     */
    protected $primaryKey = 'id';
    protected $table      = 'vlkp_value_add_service';

    /**
     * @var int
     */

    protected $visible = [
       'id',
       'name',
       'global_key',
       'is_external'
    ];

    protected $maps = [
       'is_external' => 'external_flag'
    ];

    protected $appends = [
       'id',
       'name',
       'global_key',
       'is_external'
    ];

    public function value_add_service()
    {
        return $this->hasOne(ValueAddService::class, 'service_id');
    }

    public function getValueAddServiceAttribute()
    {
        return $this->value_add_service()->first();
    }
}