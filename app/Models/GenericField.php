<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class GenericField
 * @package App\Models
 */
class GenericField extends Model
{
   use Eloquence, Mappable;

   const CREATED_AT = 'createdOn';
   const UPDATED_AT = 'updatedOn';

   /**
    * @var string
    */
   protected $primaryKey = 'genericFieldsId';

    /**
     * @var array
     */
   protected $guarded = [
       'id',
       'genericFieldsId',
   ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 'genericFieldsId',
        'generic_id' => 'genericId',
        'name'       => 'fieldName',
        'value'      => 'fieldValue',
        'created_at' => 'createdOn',
        'updated_at' => 'updatedOn',
    ];

    /**
     * @return bool|mixed
     */
    protected function getFieldValueAttribute()
    {
        $value = $this->attributes['fieldValue'];

        if (in_array($value, [1, 'yes', 'Yes'], true)) {
            return true;
        } elseif (in_array($value, [0, 'no', 'No'], true)) {
            return false;
        }

        return $value;
    }
}
