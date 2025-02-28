<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class CascadeForce extends Model
{
   use Eloquence, Mappable;

   protected $table = 'gateway_cascade_orders_preserved';

   protected $visible = [
      'order_id',
      'gateway_id',
      'profile_id',
   ];
   protected $appends = [
      'order_id',
      'profile_id',
   ];
   protected $maps = [
      'order_id'   => 'orders_id',
      'profile_id' => 'cascade_profile_id',
   ];

   public function order()
   {
      return $this->belongsTo(Order::class, 'orders_id');
   }
}