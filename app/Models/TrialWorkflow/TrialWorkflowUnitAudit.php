<?php

namespace App\Models\TrialWorkflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Lib\HasCreator;
use App\Traits\HasImmutable;

/**
 * Class TrialWorkflowUnitAudit
 * @package App\Models\TrialWorkflow
 */
class TrialWorkflowUnitAudit extends Model
{
   use HasCreator;
   use HasImmutable;

   const CREATED_BY   = 'created_by';
   const IS_IMMUTABLE = 'id'; // once audit is created we don't want to update the record

   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'trial_workflow_unit_id',
      'field_name',
      'field_name_formatted',
      'previous_value',
      'new_value',
      'created_by',
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
         $instance->checkImmutable();
      });
      static::deleting(function ($instance) {
         $instance->checkImmutable();
      });
   }

   /**
    * @return BelongsTo
    */
   public function unit(): BelongsTo
   {
      return $this->belongsTo(TrialWorkflowUnit::class);
   }
}
