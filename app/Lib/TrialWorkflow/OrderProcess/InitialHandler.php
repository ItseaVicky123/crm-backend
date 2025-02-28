<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Exceptions\TrialWorkflow\BadWorkflowRelationException;
use App\Models\TrialWorkflow\TrialWorkflowOffer;
use App\Exceptions\TrialWorkflow\WorkflowUnitNotFoundException;

/**
 * Class InitialHandler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Encalpsulates handling the initial order for trial workflow orders
 */
class InitialHandler extends Handler
{
    /**
     * @var int $trialWorkflowId
     */
    protected int $trialWorkflowId = 0;

   /**
    * InitialHandler constructor.
    * @param int $offerId
    * @param int $trialWorkflowId
    * @throws WorkflowUnitNotFoundException
    * @throws BadWorkflowRelationException
    */
   public function __construct(int $offerId, int $trialWorkflowId = 0)
   {
      // Load trial workflow and units derived from the offer ID
      //
      if ($this->loadWorkflowByOfferId($offerId, $trialWorkflowId)) {
         // If there is a relationship, the pieces must exist as well
         if ($currentUnit = $this->findUnitAtStep(1)) {
            $this->setCurrentUnit($currentUnit);

            if ($nextUnit = $this->findUnitAtStep(2)) {
               $this->setNextUnit($nextUnit);
            }
         } else {
            throw new WorkflowUnitNotFoundException;
         }
      }
   }

   /**
    * Get the starting cycle depth relative to units
    * @return int
    */
   public function getInitialCycleDepth(): int
   {
      $startDepth = -1; // Cycle depth main

      return $startDepth - $this->getUnitCount();
   }

   /**
    * Load the trial workflow and trial workflow units by offer ID
    * @param int $offerId
    * @param int $trialWorkflowId
    * @return bool
    * @throws BadWorkflowRelationException
    */
   private function loadWorkflowByOfferId(int $offerId, int $trialWorkflowId = 0): bool
   {
      // Load the trial workflow and trial workflow units associated
      //
      if ($trialWorkflowId) {
         // If a trial workflow ID is specified, fetch it
         //
         $whereClauseData = [
            ['offer_id', $offerId],
            ['trial_workflow_id', $trialWorkflowId],
         ];
      } else {
         // If a trial workflow ID is NOT specified, fetch the default
         //
         $whereClauseData = [
            ['offer_id', $offerId],
            ['is_default', 1],
         ];
      }

      if ($trialWorkflowOffer = TrialWorkflowOffer::where($whereClauseData)->first()) {
         $this->trialWorkflow   = $trialWorkflowOffer->workflow;
         $this->units           = $this->trialWorkflow->units;
         $this->trialWorkflowId = $this->trialWorkflow->id;
      } else {
         throw new BadWorkflowRelationException;
      }

      return $this->hasTrialWorkflow();
   }

    /**
     * @return int
     */
   public function getTrialWorkflowId(): int
   {
       return $this->trialWorkflowId;
   }
}
