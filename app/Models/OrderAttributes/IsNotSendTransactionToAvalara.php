<?php
namespace App\Models\OrderAttributes;
use App\Models\OrderAttribute;
class IsNotSendTransactionToAvalara extends OrderAttribute
{
   const TYPE_ID = 49;
   /**
    * @var array
    */
   protected $attributes = [
      'type_id' => self::TYPE_ID,
   ];
}
