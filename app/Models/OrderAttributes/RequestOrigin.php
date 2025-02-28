<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class RequestOrigin
 * @package App\Models\OrderAttributes
 */
class RequestOrigin extends OrderAttribute
{
   const TYPE_ID = 14;

   /**
    * @var array
    */
   protected $attributes = [
      'type_id' => self::TYPE_ID,
   ];
}
