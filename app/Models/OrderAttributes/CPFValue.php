<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class CPFValue
 * @package App\Models\OrderAttributes
 */
class CPFValue extends OrderAttribute
{
    const TYPE_ID = 24;
    const IS_IMMUTABLE = true;

   /**
    * @var array
    */
   protected $attributes = [
      'type_id' => self::TYPE_ID,
   ];
}
