<?php

namespace App\Lib\TrialWorkflow\OrderProcess;

use App\Models\TrialWorkflow\TrialWorkflowLineItem;
use App\Models\Upsell;

/**
 * Class MainOrderHandler
 * @package App\Lib\TrialWorkflow\OrderProcess
 * Handles loading the main order
 */
class MainOrderHandler extends Handler
{
   /**
    * @var int $mainOrderId
    */
   protected int $mainOrderId;

   /**
    * @var TrialWorkflowLineItem|null
    */
   protected ?TrialWorkflowLineItem $mainLintItem = null;

   /**
    * @var TrialWorkflowLineItem[] $upsellLintItems
    */
   protected array $upsellLintItems = [];

   /**
    * MainOrderHandler constructor.
    * @param int $mainOrderId
    */
   public function __construct(int $mainOrderId)
   {
      if ($this->mainOrderId = $mainOrderId) {
         // Load the main trial workflow linked line item if possible
         //
         $mainlineItem = TrialWorkflowLineItem::where([
            ['order_id', $this->mainOrderId],
            ['order_type_id', \billing_models\api\subscription_order::TYPE_MAIN],
         ])->first();

         if ($mainlineItem) {
            $this->mainLintItem = $mainlineItem;
         }

         // Load up the upsell linked line items if possible
         //
         if ($upsells = Upsell::where('main_orders_id', $this->mainOrderId)->get()) {
            if (count($upsells)) {
               foreach ($upsells as $upsell) {
                  $upsellLineItem = TrialWorkflowLineItem::where([
                     ['order_id', $upsell->upsell_orders_id],
                     ['order_type_id', \billing_models\api\subscription_order::TYPE_UPSELL],
                  ])->first();

                  if ($upsellLineItem) {
                     $this->upsellLintItems[] = $upsellLineItem;
                  }
               }
            }
         }
      }
   }

   /**
    * Determine if main line item is set
    * @return bool
    */
   public function hasMainLineItem(): bool
   {
      return (bool) $this->mainLintItem;
   }

   /**
    * Determine if upsell line items are set
    * @return bool
    */
   public function hasUpsellLineItems(): bool
   {
      return count($this->upsellLintItems) > 0;
   }

   /**
    * Determine if there are any line items linked
    * @return bool
    */
   public function hasLineItemsLinked(): bool
   {
      return $this->hasMainLineItem() || $this->hasUpsellLineItems();
   }
}
