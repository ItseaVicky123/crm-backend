<?php

namespace App\Lib\TrialWorkflow;

use App\Models\BillingModel\OrderSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Class ShippableOrderFilter
 * @package App\Lib\TrialWorkflow
 * Determine shippable and unshippables in the context of trial workflow
 */
class ShippableOrderFilter
{
   /**
    * @var int $mainOrderId
    */
   private int $mainOrderId;

   /**
    * @var int $shippableMainLineItem
    */
   private int $shippableMainLineItem = 0;

   /**
    * @var array $shippableUpsellLineItems
    */
   private array $shippableUpsellLineItems = [];

   /**
    * @var int $unshippableMainLineItem
    */
   private int $unshippableMainLineItem = 0;

   /**
    * @var array $unshippableUpsellLineItems
    */
   private array $unshippableUpsellLineItems = [];

   /**
    * UnshippableOrders constructor.
    * @param int $mainOrderId
    */
   public function __construct(int $mainOrderId)
   {
      $this->mainOrderId = $mainOrderId;

      $sql = <<<SQL
    SELECT 
          1                    AS `order_type_id`,
          `twu`.`is_shippable` AS `is_shippable`,
          `o`.`orders_id`      AS `order_id`
      FROM 
          `orders` AS `o`
      JOIN
          `trial_workflow_line_items` AS `twli`
        ON 
          `twli`.`order_id` = `o`.`orders_id`
       AND
          `twli`.`order_type_id` = 1
      JOIN 
          `trial_workflow_units` AS `twu` 
        ON 
          `twu`.`id` = `twli`.`trial_workflow_unit_id`
     WHERE
          `o`.`orders_id` = ?
     UNION
    SELECT
          2                       AS `order_type_id`,
          `twu`.`is_shippable`    AS `is_shippable`,
          `uo`.`upsell_orders_id` AS `order_id`
      FROM
          `upsell_orders` AS `uo`
      JOIN
          `trial_workflow_line_items` AS `twli`
        ON 
          `twli`.`order_id` = `uo`.`upsell_orders_id`
       AND
          `twli`.`order_type_id` = 2
      JOIN 
          `trial_workflow_units` AS `twu` 
        ON 
          `twu`.`id` = `twli`.`trial_workflow_unit_id`
     WHERE
          `uo`.`main_orders_id` = ?
SQL;

      $result = DB::select(DB::Raw($sql), [$this->mainOrderId, $this->mainOrderId]);

      if ($result && count($result)) {
         foreach ($result as $row) {
            $orderTypeId = $row->order_type_id;
            $isShippable = $row->is_shippable;
            $orderId     = $row->order_id;

            if ($orderTypeId == OrderSubscription::TYPE_MAIN) {
               if ($isShippable) {
                  $this->shippableMainLineItem = $orderId;
               } else {
                  $this->unshippableMainLineItem = $orderId;
               }
            } else {
               if ($isShippable) {
                  $this->shippableUpsellLineItems[] = $orderId;
               } else {
                  $this->unshippableUpsellLineItems[] = $orderId;
               }
            }
         }
      }
   }

   /**
    * @return int
    */
   public function getShippableMainLineItem(): int
   {
      return $this->shippableMainLineItem;
   }

   /**
    * @return int
    */
   public function getUnshippableMainLineItem(): int
   {
      return $this->unshippableMainLineItem;
   }

   /**
    * @return array
    */
   public function getShippableUpsellLineItems(): array
   {
      return $this->shippableUpsellLineItems;
   }

   /**
    * @return array
    */
   public function getUnshippableUpsellLineItems(): array
   {
      return $this->unshippableUpsellLineItems;
   }
}
