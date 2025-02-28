<?php

namespace App\Lib\LineItems;

use billing_models\api\order_product_entry;
use App\Models\TrialWorkflow\TrialWorkflowUnit;
use App\Lib\BillingModels\ShippingPriceCalculationInput;

/**
 * Class SingleLineItem
 * @package App\Lib
 * Encapsulate each line item from new order in a collection, not depending upon product indeces
 */
class SingleLineItem
{
   /**
    * @var order_product_entry|null
    */
   private ?order_product_entry $orderProductEntry = null;

   /**
    * @var string|null $index
    */
   private ?string $index = null;

   /**
    * @var int|null $variantId
    */
   private ?int $variantId = null;

   /**
    * @var float|null $price
    */
   private ?float $price = null;

   /**
    * @var mixed $discount
    */
   private $discount = null;

   /**
    * @var float $subscriptionDiscountPercent
    */
   private float $subscriptionDiscountPercent = 0;

   /**
    * @var float $subscriptionDiscountFlatAmount
    */
   private float $subscriptionDiscountFlatAmount = 0;

   /**
    * @var int $quantity
    */
   private int $quantity = 1;

   /**
    * @var float|null $basePrice
    */
   private ?float $basePrice = null;

   /**
    * @var int|null $nextRecurringProduct
    */
   private ?int $nextRecurringProduct = null;

   /**
    * @var string|null $uuid
    */
   private ?string $uuid;

   /**
    * @var bool $isUpsell
    */
   private bool $isUpsell = false;

   /**
    * @var bool $markUnshippable
    */
   private bool $markUnshippable = false;

    /**
     * @var TrialWorkflowUnit|null $trialWorkflowUnit
     */
   private ?TrialWorkflowUnit $trialWorkflowUnit = null;

    /**
     * @var bool $isStopRecurring
     */
   private bool $isStopRecurring = false;

   public function __construct()
   {
      $this->uuid = (string) (new \uuid);
   }

   /**
    * @return string
    */
   public function getUuid(): ?string
   {
      return $this->uuid;
   }

   /**
    * @return order_product_entry|null
    */
   public function getOrderProductEntry(): ?order_product_entry
   {
      return $this->orderProductEntry;
   }

   /**
    * @return bool
    */
   public function isUpsell(): bool
   {
      return $this->isUpsell;
   }

   /**
    * @param bool $isUpsell
    * @return SingleLineItem
    */
   public function setIsUpsell(bool $isUpsell): SingleLineItem
   {
      $this->isUpsell = $isUpsell;
      return $this;
   }

   /**
    * Set a reference to the product entry
    * @param order_product_entry $orderProductEntry
    * @return SingleLineItem
    */
   public function setOrderProductEntry(order_product_entry &$orderProductEntry): SingleLineItem
   {
      $this->orderProductEntry = $orderProductEntry;
      $this->markUnshippable   = $this->orderProductEntry->markUnshippable;
      $this->trialWorkflowUnit = $this->orderProductEntry->currentWorkflowUnit;
      $this->isStopRecurring   = $this->orderProductEntry->isStopRecurring;

      return $this;
   }

   /**
    * @return string|null
    */
   public function getIndex(): ?string
   {
      return $this->index;
   }

   /**
    * @param string $index
    * @return SingleLineItem
    */
   public function setIndex(string $index): SingleLineItem
   {
      $this->index = $index;
      return $this;
   }

   /**
    * @return int|null
    */
   public function getVariantId(): ?int
   {
      return $this->variantId;
   }

   /**
    * @param int $variantId
    * @return SingleLineItem
    */
   public function setVariantId(int $variantId): SingleLineItem
   {
      $this->variantId = $variantId;
      return $this;
   }


    /**
     * @return string|null
     */
    public function getProductVariantKey(): ?string
    {
        return $this->getNextRecurringProduct() . '-' . ($this->getVariantId() ?? 0);
    }

   /**
    * @return float|null
    */
   public function getPrice(): ?float
   {
      return $this->price;
   }

   /**
    * @return bool
    */
   public function isPriceSet(): bool
   {
      return ! is_null($this->price);
   }

   /**
    * @param float|null $price
    * @return SingleLineItem
    */
   public function setPrice(?float $price): SingleLineItem
   {
      $this->price = $price;
      return $this;
   }

    /**
     * Update the line item custom price, order product entry included.
     * @param float $price
     */
   public function setCustomPrice(float $price): void
   {
       if ($this->isOrderProductEntryValid()) {
           $this->orderProductEntry->setCustomPrice($price);
           $this->price = $price;
       }
   }

    /**
     * @return bool
     */
    public function isCustomPrice(): bool
    {
        $isCustomPrice = false;
        if ($this->isOrderProductEntryValid()) {
            $isCustomPrice = (bool) $this->orderProductEntry->getCustomPrice();
        }

        return $isCustomPrice;
    }

    /**
     * Update the line item preserve price flag, order product entry included.
     * @param bool $isPreserve
     */
   public function setIsPreservePrice(bool $isPreserve): void
   {
       if ($this->isOrderProductEntryValid()) {
           $this->orderProductEntry->setIsPreservePrice($isPreserve);
       }
   }

    /**
     * @return bool
     */
   public function isPreservePrice(): bool
   {
       $isPreservePrice = false;
       if ($this->isOrderProductEntryValid()) {
           $isPreservePrice = (bool) $this->orderProductEntry->getPreservePrice();
       }

       return $isPreservePrice;
   }

   /**
    * @return mixed
    */
   public function getDiscount()
   {
      return $this->discount;
   }

   /**
    * @param mixed $discount
    * @return SingleLineItem
    */
   public function setDiscount($discount): SingleLineItem
   {
      $this->discount = $discount;
      return $this;
   }

   /**
    * @return float
    */
   public function getSubscriptionDiscountPercent(): float
   {
      return $this->subscriptionDiscountPercent;
   }

   /**
    * @param float $subscriptionDiscountPercent
    * @return SingleLineItem
    */
   public function setSubscriptionDiscountPercent(float $subscriptionDiscountPercent): SingleLineItem
   {
      $this->subscriptionDiscountPercent = $subscriptionDiscountPercent;
      return $this;
   }

   /**
    * @return float
    */
   public function getSubscriptionDiscountFlatAmount(): float
   {
      return $this->subscriptionDiscountFlatAmount;
   }

   /**
    * @param float $subscriptionDiscountFlatAmount
    * @return SingleLineItem
    */
   public function setSubscriptionDiscountFlatAmount(float $subscriptionDiscountFlatAmount): SingleLineItem
   {
      $this->subscriptionDiscountFlatAmount = $subscriptionDiscountFlatAmount;
      return $this;
   }

   /**
    * @return int
    */
   public function getQuantity(): int
   {
      return $this->quantity;
   }

   /**
    * @param int $quantity
    * @return SingleLineItem
    */
   public function setQuantity(int $quantity): SingleLineItem
   {
      $this->quantity = $quantity;
      return $this;
   }

   /**
    * @return float
    */
   public function getBasePrice(): ?float
   {
      return $this->basePrice;
   }

   /**
    * @param float $basePrice
    * @return SingleLineItem
    */
   public function setBasePrice(float $basePrice): SingleLineItem
   {
      $this->basePrice = $basePrice;
      return $this;
   }

   /**
    * @return int
    */
   public function getNextRecurringProduct(): ?int
   {
      return $this->nextRecurringProduct;
   }

   public function isTrial()
   {
       $isTrial = false;
       if ($this->isOrderProductEntryValid() && ($orderProductEntry = $this->getOrderProductEntry()) && $orderProductEntry->trial_flag) {
           $isTrial = true;
       }

       return $isTrial;
   }

   /**
    * @param int $nextRecurringProduct
    * @return SingleLineItem
    */
   public function setNextRecurringProduct(int $nextRecurringProduct): SingleLineItem
   {
      $this->nextRecurringProduct = $nextRecurringProduct;
      return $this;
   }

   /**
    * Get the next recurring date from the order product entry
    * @return string
    */
   public function getNextRecurringDate(): string
   {
      $date = '';

      if ($this->isOrderProductEntryValid() && !$this->shouldStopRecurring()) {
         $date = $this->orderProductEntry->next_recurring_date;
      }

      return $date;
   }

    /**
     * @return int
     */
    public function getNextRecurringQuantity(): int
    {
        if ($this->isOrderProductEntryValid() && !$this->shouldStopRecurring()) {
            return (int) $this->orderProductEntry->next_recurring_quantity;
        }

        return 0;
    }

   /**
    * @return bool
    */
   public function isMarkUnshippable(): bool
   {
      return $this->markUnshippable;
   }

    /**
     * @param ShippingPriceCalculationInput $input
     * @return float
     */
    public function getShippingAmount(ShippingPriceCalculationInput $input): float
    {
        $shippingAmount = 0;

        // Only track this on the main
        //
        if (! $this->isUpsell()) {
            $shippingAmount = (float) $input->getDefaultPrice();

            // If it is custom override then let the default price stand
            //
            if (! $input->isCustomOverride()) {
                // Let the trial workflow unit determine the shipping price if applicable
                //
                if ($this->trialWorkflowUnit) {
                    $shippingAmount = $this->trialWorkflowUnit->getUnitShippingPrice($input);
                }
            }

            if ($this->isOrderProductEntryValid() && $this->orderProductEntry->is_prepaid) {
                // If the prepaid offer specifies shipping per cycle, multiply the shipping amount calculated thus far.
                //
                if ($this->orderProductEntry->offer->prepaid_profile->is_prepaid_shipping) {
                    $shippingAmount = $shippingAmount * $this->orderProductEntry->prepaid_cycles;
                }
            }
        }

        return $shippingAmount;
    }

   /**
    * @param int $orderId
    */
   public function setOrderId(int $orderId): void
   {
      if ($this->isOrderProductEntryValid()) {
         $this->orderProductEntry->set_order_id($orderId);
      }
   }

   /**
    * @return int
    */
   public function getBillableProductId(): int
   {
      $billableProductId = 0;

      if ($this->isOrderProductEntryValid()) {
         $billableProductId = $this->orderProductEntry->billable_product_id;
      }

      return $billableProductId;
   }

   /**
    * Get the line item step number
    * @return int|null
    */
   public function getStepNum(): ?int
   {
      $stepNum = null;

      if ($this->isOrderProductEntryValid() && $this->orderProductEntry->step_num) {
         $stepNum = $this->orderProductEntry->step_num;
      }

      return $stepNum;
   }

    /**
     * @return bool
     */
   public function shouldStopRecurring(): bool
   {
       return $this->isStopRecurring;
   }

   /**
    * @return bool
    */
   public function isOrderProductEntryValid(): bool
   {
      return (bool) $this->orderProductEntry;
   }

    /**
     * Get the line item options from the order product entry.
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];

        if ($this->isOrderProductEntryValid() && $this->orderProductEntry->options) {
            $options = $this->orderProductEntry->options;
        }

        return $options;
    }

    /**
     * Get the order ID. Can be upsell or main order ID
     * @return int
     */
    public function getOrderId(): int
    {
        $orderId = 0;

        if ($this->isOrderProductEntryValid() && $this->orderProductEntry->order_id) {
            $orderId = $this->orderProductEntry->order_id;
        }

        return $orderId;
    }

    /**
     * Get the order ID. Can be upsell or main order ID
     * @return int
     */
    public function getOrderTypeId(): int
    {
        $orderTypeId = 0;

        if ($this->isOrderProductEntryValid() && $this->orderProductEntry->type_id) {
            $orderTypeId = $this->orderProductEntry->type_id;
        }

        return $orderTypeId;
    }

    /**
     * Get the number of items in a given line item
     * @param bool $excludeFree
     * @return int
     */
    public function getItemCount(bool $excludeFree = true): int
    {
        $count = 0;

        if ($this->isOrderProductEntryValid()) {
            $lineItemPrice = $this->orderProductEntry->getLineItemTotal();

            if (!$excludeFree || ($lineItemPrice > 0)) {
                if ($billableBundleChildren = $this->orderProductEntry->billableBundleChildren) {
                    foreach ($billableBundleChildren as $bundleChild) {
                        $count += $bundleChild['quantity'];
                    }
                } else {
                    $count += $this->orderProductEntry->billable_quantity;
                }
            }
        }

        return $count;
    }

    /**
     * @param bool $excludeFree
     * @return int
     */
    public function getNextRecurringItemCount(bool $excludeFree = true): int
    {
        $count = 0;

        if ($this->isOrderProductEntryValid()) {
            $lineItemPrice = $this->orderProductEntry->getLineItemTotal();

            if (!$excludeFree || ($lineItemPrice > 0)) {
                if ($billableBundleChildren = $this->orderProductEntry->billableBundleChildren) {
                    foreach ($billableBundleChildren as $bundleChild) {
                        $count += $bundleChild['quantity'];
                    }
                } else {
                    $count += $this->orderProductEntry->next_recurring_quantity;
                }
            }
        }

        return $count;
    }

    /**
     * Get the billable quantity.
     * @return int
     */
    public function getBillableQuantity(): int
    {
        $billableQuantity = 0;
        if ($this->isOrderProductEntryValid()) {
            $orderProduct = $this->getOrderProductEntry();
            if ($billableBundleChildren = $orderProduct->billableBundleChildren) {
                foreach($billableBundleChildren as $bundleChild) {
                    $billableQuantity += $bundleChild['quantity'];
                }
            } else {
                $billableQuantity = $this->orderProductEntry->billable_quantity;
            }
        }

        return $billableQuantity;
    }
}
