<?php

namespace App\Models\TrialWorkflow;

use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class TrialWorkflowJourney
 * @package App\Models\TrialWorkflow
 * Encapsulate the whole line item chain in a container
 */
class TrialWorkflowJourney extends Model
{
   use HasCreator;

   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'order_id',
      'order_type_id',
      'initial_position',
   ];

   /**
    * @return HasMany
    */
   public function lineItems(): HasMany
   {
      return $this->hasMany(TrialWorkflowLineItem::class, 'trial_workflow_journey_id');
   }
}
