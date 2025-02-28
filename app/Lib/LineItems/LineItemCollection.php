<?php

namespace App\Lib\LineItems;

use App\Models\Product;
use App\Models\ProductPriceType;
use Illuminate\Support\Collection;

/**
 * Class LineItemCollection
 * @package App\Lib
 * Encapsulate each line item from new order in a collection, not depending upon product indeces
 */
class LineItemCollection extends Collection
{
   /**
    * Fetch the main line item from the collection
    * @return SingleLineItem|null
    */
   public function findMain(): ?SingleLineItem
   {
      $mainLineItem = null;

      if (count($this->items)) {
         /**
          * @var SingleLineItem $item
          */
         foreach ($this->items as $item) {
            if (! $item->isUpsell()) {
               $mainLineItem = $item;
               break;
            }
         }
      }

      return $mainLineItem;
   }

   /**
    * Fetch array of upsells
    * @return array
    */
   public function findUpsells(): array
   {
      $upsells = [];

      if (count($this->items)) {
         /**
          * @var SingleLineItem $item
          */
         foreach ($this->items as $item) {
            if ($item->isUpsell()) {
               $upsells[$item->getUuid()] = $item;
            }
         }
      }

      return $upsells;
   }

   /**
    * Fetch item by UUID auto generated within
    * @param string $uuid
    * @return SingleLineItem|null
    */
   public function findByUuid(string $uuid): ?SingleLineItem
   {
      $itemFound = null;

      if (count($this->items)) {
         /**
          * @var SingleLineItem $item
          */
         foreach ($this->items as &$item) {
            if ($item->getUuid() === $uuid) {
               $itemFound = $item;
               break;
            }
         }
      }

      return $itemFound;
   }

    /**
     * Ensure there aren't multiple line items with the same bundle.
     * @throws \Exception
     */
    public function preventDuplicateBundles(): void
    {
        $map                  = [];
        $nextRecurringBundles = [];

        /**
         * @var SingleLineItem $item
         */
        foreach ($this as $item) {
            $productId    = $item->getBillableProductId();
            $productModel = Product::findOrFail($productId);

            if ($nextRecurringProduct = $item->getNextRecurringProduct()) {
                $nextRecurringProductModel = Product::findOrFail($nextRecurringProduct);

                // Save next recurring products for a later calculation
                //
                if ($nextRecurringProductModel->is_bundle) {
                    $nextRecurringBundles[] = $nextRecurringProduct;
                }
            }

            if ($productModel->is_bundle) {
                if (isset($map[$productId])) {
                    throw new \Exception("Multiple line items with the same product bundle ({$productId}) are forbidden.");
                } else {
                    $map[$productId] = true;
                }
            }
        }

        // Make sure there are no duplicate next recurring bundles
        //
        if ($nextRecurringBundles && $map) {
            $existsMap = [];

            foreach ($nextRecurringBundles as $nextRecurringBundle) {
                if (isset($existsMap[$nextRecurringBundle])) {
                    throw new \Exception("Multiple next recurring line items with the same product bundle ({$nextRecurringBundle}) are forbidden.");
                } else {
                    $existsMap[$nextRecurringBundle] = true;
                }
            }
        }
    }

    /**
     * Get total item count including bundle children.
     * @param bool $excludeFree
     * @return int
     */
    public function getTotalItemCount(bool $excludeFree = true): int
    {
        $count = 0;

        /**
         * @var SingleLineItem $singleLineItem
         */
        foreach ($this as $singleLineItem) {
            $count += $singleLineItem->getItemCount($excludeFree);
        }

        return $count;
    }


    /**
     * Get total item count including bundle children.
     * @param bool $excludeFree
     * @return int
     */
    public function getTotalNextRecurringItemCount(bool $excludeFree = true): int
    {
        $count = 0;

        foreach ($this as $singleLineItem) {
            //if billing preserve count is off it will return 0
            $count += max($singleLineItem->getNextRecurringItemCount($excludeFree), 1);
        }

        return $count;
    }

    /**
     * @return float
     */
    public function getLineItemsSubtotal(): float
    {
        $subtotal = 0;

        /**
         * @var SingleLineItem $singleLineItem
         */
        foreach ($this as $singleLineItem) {
            if ($singleLineItem->isOrderProductEntryValid()) {
                $subtotal += $singleLineItem->getOrderProductEntry()->getLineItemTotal() * $singleLineItem->getQuantity();
            }
        }

        return $subtotal;
    }

    /**
     * @return float
     */
    public function getNextRecurringLineItemsSubtotal(): float
    {
        $subtotal = 0;

        /**
         * @var SingleLineItem $singleLineItem
         */
        foreach ($this as $singleLineItem) {
            if ($singleLineItem->isOrderProductEntryValid()) {
                $subtotal += $singleLineItem->getOrderProductEntry()->getNextRecurringLineItemTotal() * $singleLineItem->getQuantity();
            }
        }

        return $subtotal;
    }

    /**
     * Fetch a map of single line items with a subtotal greater than 0.
     * @return array
     */
    public function getLineItemSubtotalMap(): array
    {
        $map = [];

        /**
         * @var SingleLineItem $singleLineItem
         */
        foreach ($this as $singleLineItem) {
            if ($singleLineItem->isOrderProductEntryValid()) {
                $orderProduct = $singleLineItem->getOrderProductEntry();
                if (($billableBundleChildren = $orderProduct->billableBundleChildren)) {
                    $subtotal        = 0;
                    $countTotal      = 0;
                    $useFixedPrice   = $orderProduct->bundle->price_type_id != ProductPriceType::FIXED ? false : true;
                    $discountedPrice = (float) $orderProduct->product_price === (float) $orderProduct->base_product_price ? 0 : (float) $orderProduct->product_price;
                    if ($useFixedPrice) {
                        $subtotal = $orderProduct->getLineItemTotal();
                    } else if ($discountedPrice) {
                        $subtotal = $discountedPrice;
                    }
                    foreach($billableBundleChildren as $bundleChild) {
                        $countTotal += $bundleChild['quantity'];
                        if (! $useFixedPrice && ! $discountedPrice) {
                            $subtotal += $bundleChild['price'];
                        }
                    }
                    $map[$singleLineItem->getUuid()] = $subtotal / $countTotal;
                } else if ($subtotal = $orderProduct->getLineItemTotal()) {
                    $map[$singleLineItem->getUuid()] = $subtotal;
                }
            }
        }

        return $map;
    }

    /**
     * Fetch a map of single line items with a subtotal greater than 0.
     * @return array
     */
    public function getNextRecurringLineItemSubtotalMap(): array
    {
        $map = [];

        /**
         * @var SingleLineItem $singleLineItem
         */
        foreach ($this as $singleLineItem) {
            if ($singleLineItem->isOrderProductEntryValid()) {
                $orderProduct = $singleLineItem->getOrderProductEntry();
                if (($billableBundleChildren = $orderProduct->billableBundleChildren)) {
                    $subtotal        = 0;
                    $countTotal      = 0;
                    $useFixedPrice   = $orderProduct->bundle->price_type_id != ProductPriceType::FIXED ? false : true;
                    $discountedPrice = (float) $orderProduct->product_price === (float) $orderProduct->base_product_price ? 0 : (float) $orderProduct->product_price;
                    if ($useFixedPrice) {
                        $subtotal = $orderProduct->getNextRecurringLineItemTotal();
                    } else if ($discountedPrice) {
                        $subtotal = $discountedPrice;
                    }
                    foreach($billableBundleChildren as $bundleChild) {
                        $countTotal += $bundleChild['quantity'];
                        if (! $useFixedPrice && ! $discountedPrice) {
                            $subtotal += $bundleChild['price'];
                        }
                    }
                    $map[$singleLineItem->getUuid()] = $subtotal / $countTotal;
                } else if ($subtotal = $orderProduct->getNextRecurringLineItemTotal()) {
                    $map[$singleLineItem->getUuid()] = $subtotal;
                }
            }
        }

        return $map;
    }

    /**
     * Remove all items from collection
     */
    public function forgetAll(): void
    {
        $this->items = [];
    }
}
