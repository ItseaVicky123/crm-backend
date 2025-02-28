<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class RequestOriginSent
 * @package App\Models\OrderAttributes
 */
class RequestOriginSent extends OrderAttribute
{
   const TYPE_ID = 19;

   /**
    * @var array
    */
   protected $attributes = [
      'type_id' => self::TYPE_ID,
   ];
}
