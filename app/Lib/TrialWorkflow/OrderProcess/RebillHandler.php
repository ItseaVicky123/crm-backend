<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Models\TrialWorkflow\PairedProductDelay;
use App\Models\TrialWorkflow\TrialWorkflowLineItem;
use App\Models\TrialWorkflow\TrialWorkflowUnit;
use App\Exceptions\TrialWorkflow\WorkflowUnitNotFoundException;

/**
 * Class RebillHandler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Encapsulates handling the recurring order for trial workflow orders
 */
class RebillHandler extends Handler
{
   /**
    * @var TrialWorkflowLineItem|null $workflowLineItem
    */
   protected ?TrialWorkflowLineItem $workflowLineItem = null;

   /**
    * @var int $customNextProductId
    */
   private int $customNextProductId = 0;

   /**
    * @var bool $markUnshippable
    */
   private bool $markUnshippable = false;

   /**
    * @var int|null $customDays
    */
   private ?int $customDays = null;

   /**
    * @var TrialWorkflowUnit|null
    */
   private ?TrialWorkflowUnit $nextUnitChild = null;

   /**
    * RebillHandler constructor.
    * @param int $parentOrderId
    * @param int $orderTypeId
    * @throws WorkflowUnitNotFoundException
    */
   public function __construct(int $parentOrderId, int $orderTypeId)
   {
      if ($this->loadWorkflowByParentOrderId($parentOrderId, $orderTypeId)) {
         if ($this->isCurrentUnitValid()) {
            $currentUnitStep = $this->currentUnit->step_number;

            if ($nextUnit = $this->findUnitAtStep($currentUnitStep + 1)) {
               $this->setNextUnit($nextUnit);

               if ($nextUnitChild = $this->findUnitAtStep($nextUnit->step_number + 1)) {
                  $this->setNextUnitChild($nextUnitChild);
               }
            }

            // Get the previous step if we are passed step 1
            //
            if ($currentUnitStep > 1) {
               if ($previousUnit = $this->findUnitAtStep($currentUnitStep - 1)) {
                  $this->setPreviousUnit($previousUnit);
               }
            }
         } else {
            throw new WorkflowUnitNotFoundException;
         }
      }
   }

   /**
    * Checks rule for processing rebill values
    * @return bool
    */
   public function canProcessRecurringValues(): bool
   {
      return $this->hasTrialWorkflow() && $this->isWorkflowLineItemValid();
   }

   public function processRecurringValues(): void
   {
      // If next unit is defined, get the next recurring data from there
      //
      if ($this->isNextUnitValid()) {
         // Set next recurring product to the one defined in the next workflow unit if set
         //
         if ($this->nextUnit->hasCustomProduct()) {
            $this->customNextProductId = $this->nextUnit->product_id;
         }

         // Set next recurring date based upon custom duration if set
         //
         if ($this->nextUnit->hasDuration()) {
            $this->customDays = $this->nextUnit->duration;
         }

         // Mark order as unshippable when configured
         //
         if (! $this->nextUnit->is_shippable) {
            $this->markUnshippable = true;
         }
      } else {
         // If there is no next unit, use the original subscription product ID
         //
         $this->customNextProductId = $this->workflowLineItem->subscription_product_id;
      }
   }

   /**
    * Check that the workflow line item is populated with an instance
    * @return bool
    */
   public function isWorkflowLineItemValid(): bool
   {
      return (bool) $this->workflowLineItem;
   }

   /**
    * @return TrialWorkflowLineItem|null
    */
   public function getWorkflowLineItem(): ?TrialWorkflowLineItem
   {
      return $this->workflowLineItem;
   }

   /**
    * @return int
    */
   public function getCustomNextProductId(): int
   {
      return $this->customNextProductId;
   }

   /**
    * @return bool
    */
   public function getMarkUnshippable(): bool
   {
      return $this->markUnshippable;
   }

   /**
    * @return int|null
    */
   public function getCustomDays(): ?int
   {
      return $this->customDays;
   }

   /**
    * @return bool
    */
   public function hasCustomDays(): bool
   {
      return ! is_null($this->customDays);
   }

   /**
    * @return bool
    */
   public function hasCustomNextProductId(): bool
   {
      return $this->customNextProductId > 0;
   }

   /**
    * @param TrialWorkflowUnit $unit
    */
   protected function setNextUnitChild(TrialWorkflowUnit $unit): void
   {
      $this->nextUnitChild = $unit;
   }

   /**
    * @return bool
    */
   public function hasNextUnitChild(): bool
   {
      return (bool) $this->nextUnitChild;
   }

   /**
    * @return TrialWorkflowUnit|null
    */
   public function getNextUnitChild(): ?TrialWorkflowUnit
   {
      return $this->nextUnitChild;
   }

   /**
    * Save the next trial workflow line item
    * @param int $newOrderId
    * @param int $orderTypeId
    * @param int $productId
    * @param int $variantId
    */
   public function saveNewLineItem(int $newOrderId, int $orderTypeId, int $productId = 0, int $variantId = 0): void
   {
      // Only save if there is an associated trial workflow unit
      //
      if ($this->isNextUnitValid()) {
         TrialWorkflowLineItem::create([
            'trial_workflow_unit_id'        => $this->nextUnit->id,
            'trial_workflow_journey_id'     => $this->workflowLineItem->trial_workflow_journey_id,
            'order_id'                      => $newOrderId,
            'order_type_id'                 => $orderTypeId,
            'subscription_product_id'       => $this->workflowLineItem->subscription_product_id,
            'subscription_variant_id'       => $this->workflowLineItem->subscription_variant_id,
            'subscription_product_price'    => $this->workflowLineItem->subscription_product_price,
            'subscription_product_quantity' => $this->workflowLineItem->subscription_product_quantity,
            'billing_model_id'              => $this->workflowLineItem->billing_model_id,
         ]);

         if ($this->nextUnit->shouldDelayPairedProduct()) {
            PairedProductDelay::create([
               'order_id'      => $newOrderId,
               'order_type_id' => $orderTypeId,
               'product_id'    => $productId,
               'variant_id'    => $variantId,
            ]);
         }
      }
   }

   /**
    * Load the workflow and workflow units from parent order id and order type (main/upsell)
    * @param int $parentOrderId
    * @param int $orderTypeId
    * @return bool
    */
   private function loadWorkflowByParentOrderId(int $parentOrderId, int $orderTypeId): bool
   {
      $this->workflowLineItem = TrialWorkflowLineItem::where([
         ['order_id', $parentOrderId],
         ['order_type_id', $orderTypeId]
      ])->first();

      if ($this->workflowLineItem) {
         $this->currentUnit   = $this->workflowLineItem->unit;
         $this->trialWorkflow = $this->currentUnit->workflow;
         $this->units         = $this->trialWorkflow->units;
      }

      return $this->isCurrentUnitValid();
   }
}
