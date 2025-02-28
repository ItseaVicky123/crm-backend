<?php


namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
   const CREATED_AT = 'createdOn';
   const UPDATED_AT = null;

   /**
    * @param Builder $query
    * @param int     $orderId
    * @return Builder
    */
   public function scopeForOrder($query, $orderId)
   {
      return $query->where('order_id', $orderId);
   }
}
