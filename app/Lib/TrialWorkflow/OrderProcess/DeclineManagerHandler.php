<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Models\TrialWorkflow\TrialWorkflowUnit;

/**
 * Class DeclineManagerHandler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Handles decline functionality for Trial Workflow module
 */
class DeclineManagerHandler extends MainOrderHandler
{
   /**
    * Determine if system should cancel parent order on max retries
    * @return bool
    */
   public function shouldCancelParentOnMaxRetries(): bool
   {
      if ($this->hasMainLineItem()) {
         /**
          * @var TrialWorkflowUnit $mainUnit
          */
         $mainUnit = $this->mainLintItem->unit;

         if ($mainUnit->is_parent_cancellable) {
            return true;
         }
      }

      if ($this->hasUpsellLineItems()) {
         foreach ($this->upsellLintItems as $upsellLineItem) {
            /**
             * @var TrialWorkflowUnit $upsellUnit
             */
            $upsellUnit = $upsellLineItem->unit;

            if ($upsellUnit->is_parent_cancellable) {
               return true;
            }
         }
      }

      return false;
   }
}
