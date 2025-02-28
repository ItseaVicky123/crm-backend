<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;

/**
 * Class InitialDunning
 * @package App\Models\OrderAttributes
 */
class InitialDunning extends OrderAttribute
{
   const TYPE_ID = 34;

   /**
    * @var array
    */
   protected $attributes = [
      'type_id' => self::TYPE_ID,
   ];
}
