<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Scopes\GatewayForceCheckScope as CheckScope;

class CheckForce extends Model
{
   use Eloquence, Mappable;

   protected $table = 'gateway_force_preserved';

   protected $visible = [
      'order_id',
      'is_preserved',
      'gateway_id',
   ];
   protected $maps = [
      'order_id'     => 'orders_id',
      'is_preserved' => 'preserve',
   ];
   protected $appends = [
      'is_preserved',
   ];

   public $timestamps = false;

   protected static function boot()
   {
      parent::boot();

      static::addGlobalScope(new CheckScope);
   }

   public function order()
   {
      return $this->belongsTo(Order::class, 'orders_id');
   }
}