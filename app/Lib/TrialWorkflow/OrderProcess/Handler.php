<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Models\TrialWorkflow\TrialWorkflow;
use App\Models\TrialWorkflow\TrialWorkflowUnit;
use Illuminate\Support\Collection;

/**
 * Class Handler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Encapsulates Trial Workflow related tasks to do during the order process
 */
class Handler
{
   /**
    * @var TrialWorkflow|null $trialWorkflow
    */
   protected ?TrialWorkflow $trialWorkflow = null;

   /**
    * @var Collection|null $units
    */
   protected ?Collection $units = null;

   /**
    * @var TrialWorkflowUnit|null $currentUnit
    */
   protected ?TrialWorkflowUnit $currentUnit = null;

   /**
    * @var TrialWorkflowUnit|null $previousUnit
    */
   protected ?TrialWorkflowUnit $previousUnit = null;

   /**
    * @var TrialWorkflowUnit|null $nextUnit
    */
   protected ?TrialWorkflowUnit $nextUnit = null;

   /**
    * Determine if the offer has a trial workflow attached with units
    * @return bool
    */
   public function hasTrialWorkflow(): bool
   {
      return $this->trialWorkflow && count($this->units);
   }

   /**
    * Retrieve the current trial workflow unit
    * @return TrialWorkflowUnit|null
    */
   public function getCurrentUnit(): ?TrialWorkflowUnit
   {
      return $this->currentUnit;
   }

   /**
    * Retrieve the next trial workflow unit relative to the current one
    * @return TrialWorkflowUnit|null
    */
   public function getNextUnit(): ?TrialWorkflowUnit
   {
      return $this->nextUnit;
   }

   /**
    * Retrieve the previous trial workflow unit relative to the current one
    * @return TrialWorkflowUnit|null
    */
   public function getPreviousUnit(): ?TrialWorkflowUnit
   {
      return $this->previousUnit;
   }

   /**
    * Determine product ID from the current trial workflow unit
    * @return int
    */
   public function getCurrentUnitProductId(): int
   {
      return ($this->isCurrentUnitValid() ? $this->currentUnit->product_id : 0);
   }

   /**
    * Fetch a trial workflow unit at a specific step number within the workflow
    * @param $stepNumber
    * @return TrialWorkflowUnit|null
    */
   protected function findUnitAtStep($stepNumber): ?TrialWorkflowUnit
   {
      $unitFound = null;

      foreach ($this->units as $unit) {
         if ($unit->step_number == $stepNumber) {
            $unitFound = $unit;
            break;
         }
      }

      return $unitFound;
   }

   /**
    * @param TrialWorkflowUnit $unit
    */
   protected function setCurrentUnit(TrialWorkflowUnit $unit): void
   {
      $this->currentUnit = $unit;
   }

   /**
    * @param TrialWorkflowUnit $unit
    */
   protected function setNextUnit(TrialWorkflowUnit $unit): void
   {
      $this->nextUnit = $unit;
   }

   /**
    * @param TrialWorkflowUnit $unit
    */
   protected function setPreviousUnit(TrialWorkflowUnit $unit): void
   {
      $this->previousUnit = $unit;
   }

   /**
    * Ensure current workflow unit is an valid instance
    * @return bool
    */
   public function isCurrentUnitValid(): bool
   {
      return (bool) $this->currentUnit;
   }

   /**
    * Ensure previous workflow unit is an valid instance
    * @return bool
    */
   public function isPreviousUnitValid(): bool
   {
      return (bool) $this->previousUnit;
   }

   /**
    * Ensure next workflow unit is an valid instance
    * @return bool
    */
   public function isNextUnitValid(): bool
   {
      return (bool) $this->nextUnit;
   }

   /**
    * Return the number of trial workflow units
    * @return int
    */
   public function getUnitCount(): int
   {
      return count($this->units);
   }
}
