<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Models\TrialWorkflow\TrialWorkflowUnit;

/**
 * Class NotificationHandler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Handles notification functionality for Trial Workflow module
 */
class NotificationHandler extends MainOrderHandler
{
   /**
    * Find the first instance of is_notifiable equalling false
    * @return bool
    */
   public function shouldSuppressOrderConfirmation(): bool
   {
      if ($this->hasMainLineItem()) {
         /**
          * @var TrialWorkflowUnit $mainUnit
          */
         $mainUnit = $this->mainLintItem->unit;

         if (! $mainUnit->is_notifiable) {
            return true;
         }
      }

      if ($this->hasUpsellLineItems()) {
         foreach ($this->upsellLintItems as $upsellLineItem) {
            /**
             * @var TrialWorkflowUnit $upsellUnit
             */
            $upsellUnit = $upsellLineItem->unit;

            if (! $upsellUnit->is_notifiable) {
               return true;
            }
         }
      }

      return false;
   }
}
