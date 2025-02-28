<?php

namespace App\Models\TrialWorkflow;

use App\Models\Offer\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;

/**
 * Class TrialWorkflow
 * @package App\Models\TrialWorkflow
 */
class TrialWorkflow extends Model
{
   use LimeSoftDeletes;
   use HasCreator;

   // HasCreator constants
   //
   const CREATED_BY = 'created_by';
   const UPDATED_BY = 'updated_by';

   // LimeSoftDeletes constants
   //
   const DELETED_FLAG = 'is_deleted';
   const ACTIVE_FLAG  = 'is_active';

   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'name',
      'is_active',
      'created_by',
      'updated_by',
   ];

   /**
    * Boot functions - what to set when an instance is created.
    * Hook into instance actions
    */
   public static function boot()
   {
      parent::boot();
      static::creating(function ($instance) {
         $instance->created_by = get_current_user_id();
      });
      static::updating(function ($instance) {
         $instance->updated_by = get_current_user_id();
      });
      static::deleting(function ($instance) {
         $instance->updated_by = get_current_user_id();
      });
   }

   /**
    * Determine if this trial workflow has any related orders/upsell orders
    * @return bool
    */
   public function hasRelatedOrders(): bool
   {
      return $this->orders()->count() > 0;
   }

   /**
    * @return HasMany
    */
   public function units(): HasMany
   {
      return $this->hasMany(TrialWorkflowUnit::class, 'trial_workflow_id');
   }

   /**
    * @return HasMany
    */
   public function audits(): HasMany
   {
      return $this->hasMany(TrialWorkflowAudit::class, 'trial_workflow_id');
   }

   /**
    * Fetch offers through trial_workflow_offers
    * @return BelongsToMany
    */
   public function offers(): BelongsToMany
   {
      return $this->belongsToMany(Offer::class, 'trial_workflow_offers');
   }

   /**
    * @return HasManyThrough
    */
   public function orders(): HasManyThrough
   {
      return $this->hasManyThrough(
         TrialWorkflowLineItem::class,
         TrialWorkflowUnit::class,
         'trial_workflow_id',
         'trial_workflow_unit_id'
      );
   }
}
