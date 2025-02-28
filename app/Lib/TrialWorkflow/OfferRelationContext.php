<?php

namespace App\Lib\TrialWorkflow;
use App\Exceptions\TrialWorkflow\WorkflowOfferRelationCreateException;
use App\Exceptions\TrialWorkflow\WorkflowOfferRelationDeleteException;
use App\Models\TrialWorkflow\TrialWorkflowOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class OfferRelationContext
 * @package App\Lib\TrialWorkflow
 * @deprecate
 */
class OfferRelationContext
{
   private array $trialWorkflowIds;

   /**
    * OfferRelationContext constructor.
    * @param array $payload
    */
   public function __construct(array $payload = [])
   {
      $this->trialWorkflowIds = $payload;
   }

   /**
    * Encapsulation for batch saving offer trial workflow relation
    * @param int $offerId
    * @return bool
    * @throws WorkflowOfferRelationCreateException
    * @throws WorkflowOfferRelationDeleteException
    */
   public function save(int $offerId): bool
   {
      $hasDefault = false;
      $inserts    = [];

      foreach ($this->trialWorkflowIds as $data) {
         $relation = new Collection($data);
         $isDefault = 0;

         if (! $hasDefault && $relation->has('is_default')) {
            $isDefault  = $relation->get('is_default');
            $hasDefault = ($isDefault == 1);
         }

         $inserts[] = [
            'trial_workflow_id' => $relation['id'],
            'offer_id'          => $offerId,
            'is_default'        => $isDefault,
         ];
      }

      // Wipe all current relationships
      //
      TrialWorkflowOffer::where('offer_id', $offerId)->delete();

      // This is a blanket assignment, so if there is no default make the first item the default
      //
      if (! $hasDefault) {
         $data[0]['is_default'] = 1;
      }

      if (! TrialWorkflowOffer::insert($inserts)) {
         throw new WorkflowOfferRelationCreateException;
      }

      return true;
   }
}