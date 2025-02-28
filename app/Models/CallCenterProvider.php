<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Sofa\Eloquence\Mappable;

/**
 * Class CallCenterProvider
 * @package App\Models
 */
class CallCenterProvider extends BaseModel
{
   use Mappable;

   const CREATED_AT = 'createdOn';
   const UPDATED_AT = null;

   /**
    * @var string
    */
   protected $table = 'call_center_provider';

   /**
    * @var int
    */
   public $perPage = 100;

   /**
    * @var array
    */
   public $visible = [
      'name',
      'id',
   ];

   /**
    * @var array
    */
   public $appends = [
      'name',
   ];

    /**
     * @var array
     */
    protected $maps = [
        'account_name' => 'account.name',
        'generic_id'   => 'genericId',
        'campaign_id'  => 'campaignId',
        'is_active'    => 'active',
        'created_at'   => self::CREATED_AT,
    ];

   protected static function boot()
   {
       parent::boot();

       static::addGlobalScope(new ActiveScope());
   }

    /**
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function sso_user()
   {
      return $this->belongsTo(SsoUser::class, 'provider_account_id', 'account_id');
   }

   public function provider_object()
   {
      return $this->hasOne(ProviderObject::class, 'account_id', 'account_id')
         ->where('provider_type_id', '=', 16);
   }

    /**
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
   public function getNameAttribute()
   {
      return $this->provider_object->name;
   }
}
