<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Class UserRole
 * @package App\Models\Campaign
 */
class UserRole extends Model
{
   use SoftDeletes;

   const PLACE_ORDER_ROLE_ID = 73;

    /**
     * @var string
     */
   public $table = 'campaign_user_roles';

   /**
    * @var bool
    */
   public $timestamps = true;

   /**
    * @var array
    */
   protected $dates = [
      'created_at',
      'updated_at',
      'deleted_at',
   ];

   /**
    * @var array
    */
   protected $fillable = [
      'admin_id',
      'campaign_id',
      'role_id',
      'created_by',
      'updated_by',
   ];

   /**
    * @var array
    */
   protected $visible = [
      'admin_id',
      'campaign_id',
      'role_id',
      'created_by',
      'created_at',
      'updated_by',
      'updated_at',
   ];

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
   public function user()
   {
      return $this->hasOne(User::class, 'admin_id', 'admin_id');
   }

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
   public function role()
   {
      return $this->hasOne(Role::class, 'id', 'role_id');
   }

   /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
   public function campaign()
   {
      return $this->hasOne(Campaign::class, 'c_id', 'campaign_id');
   }
}
