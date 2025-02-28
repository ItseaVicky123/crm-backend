<?php

namespace App\Models\TrialWorkflow;

use App\Models\Offer\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class TrialWorkflowOffer
 * @package App\Models\TrialWorkflow
 */
class TrialWorkflowOffer extends Model
{
   use LimeSoftDeletes;

   // LimeSoftDeletes constants
   //
   const DELETED_FLAG = 'is_deleted';
   const ACTIVE_FLAG  = 'is_active';

   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'trial_workflow_id',
      'offer_id',
      'is_default',
   ];

   /**
    * Get the Trial Workflow that owns this Trial Workflow Offer pivot table row
    * @return BelongsTo
    */
   public function workflow(): BelongsTo
   {
      return $this->belongsTo(TrialWorkflow::class, 'trial_workflow_id');
   }

   /**
    * Get the Offer that owns this Trial Workflow Offer pivot table row
    * @return BelongsTo
    */
   public function offer(): BelongsTo
   {
      return $this->belongsTo(Offer::class, 'offer_id');
   }
}
