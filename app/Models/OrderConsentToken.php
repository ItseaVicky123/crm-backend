<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderConsentToken
 * @package App\Models
 */
class OrderConsentToken extends Model
{
   use SoftDeletes;

   /**
    * @var bool
    */
   public $incrementing = false;

    /**
     * @var string
     */
   protected $primaryKey = 'token';

   /**
    * @var array
    */
   protected $visible = [
      'token',
      'order_id',
      'order_consent_type_id',
      'destination',
      'expires_at',
      'created_at',
      'updated_at',
   ];

   /**
    * @var array
    */
   protected $fillable = [
      'token',
      'order_id',
      'order_consent_type_id',
      'destination',
      'expires_at',
   ];

   public static function boot()
   {
      parent::boot();

      static::creating(function($consentToken) {
         $consentToken->token      = (string) new \uuid();
         $consentToken->expires_at = Carbon::now()
            ->addDays(\configSettings::get('CONSENT_TOKEN_EXPIRATION_DAYS'))
            ->toDateTimeString();
      });
   }

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
   public function order_consent_type()
   {
      return $this->hasOne(OrderConsentType::class);
   }

   /**
    * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
    */
   public function getOrderConsentTypeAttribute()
   {
      return $this->order_consent_type()->first();
   }

   public function __toString()
   {
      return $this->getAttribute('token');
   }
}
