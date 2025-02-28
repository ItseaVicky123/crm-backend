<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class GatewayForce extends Model
{
   use Eloquence, Mappable;
   const CREATED_AT = 'date_in';
   const UPDATED_AT = 'date_updated';
   /**
    * @var string
    */
   protected $table = 'gateway_force_preserved';
   /**
    * @var string[]
    */
   protected $visible = [
      'order_id',
      'is_preserved',
      'gateway_id',
   ];
   /**
    * @var string[]
    */
   protected $maps = [
      'order_id'     => 'orders_id',
      'is_preserved' => 'preserve',
      'created_at'   => self::CREATED_AT,
      'updated_at'   => self::UPDATED_AT,
   ];
   /**
    * @var string[]
    */
   protected $appends = [
      'is_preserved',
   ];
   /**
    * @var string[]
    */
   protected $guarded = [
      'id',
   ];
   /**
    * @var int[]
    */
   protected $attributes = [
      'is_check' => 0,
   ];
}