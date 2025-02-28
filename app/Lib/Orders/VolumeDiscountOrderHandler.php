<?php

namespace App\Lib\Orders;

use App\Lib\LineItems\DiscountDistributor;
use App\Models\Order;
use App\Models\OrderAttributes\VolumeDiscountRecurring;
use App\Models\OrderLineItems\OrderProductVolumeDiscountPrice;
use App\Models\OrderLineItems\VolumeDiscount as VolumeDiscountOrderTotal;
use App\Models\OrderHistoryNote;
use App\Models\OrderLineItems\VolumeDiscountRebill as VolumeDiscountRebillOrderTotal;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Readers\OrderReader;
use App\Models\Upsell;
use App\Models\UpsellProductVolumeDiscountPrice;
use App\Models\User;
use App\Models\VolumeDiscounts\VolumeDiscount;
use App\Models\VolumeDiscounts\VolumeDiscountCampaign;
use App\Models\VolumeDiscounts\VolumeDiscountQuantity;
use App\Models\OrderAttributes\VolumeDiscount as VolumeDiscountOrderAttribute;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class VolumeDiscountOrderHandler
 * @package App\Lib\Orders
 */
class VolumeDiscountOrderHandler
{
    /**
     * @var int $orderId
     */
    protected int $orderId;

    /**
     * @var int $excludeUpsellId
     */
    protected int $excludeUpsellId = 0;

    /**
     * @var VolumeDiscountQuantity|null $volumeDiscountQuantity
     */
    protected ?VolumeDiscountQuantity $volumeDiscountQuantity = null;

    /**
     * @var float $discountAmount
     */
    protected float $discountAmount = 0;

    /**
     * @var int $discountId
     */
    protected int $discountId = 0;

    /**
     * @var array $items
     */
    protected array $items = [];

    /**
     * @var array $discountPriceMap
     */
    protected array $discountPriceMap = [];

    /**
     * @var VolumeDiscount|null $volumeDiscount
     */
    public ?VolumeDiscount $volumeDiscount = null;

    /**
     * @var Order|null $order
     */
    public ?Order $order = null;

    /**
     * @var Collection $lineItems
     */
    public Collection $lineItems;

    /**
     * @var array $excludeLineItem
     */
    public array $excludeLineItem = [];

    /**
     * @var int $rebill
     */
    public int $rebill = 0;

    /**
     * @var array $products
     */
    public array $products = [];

    /**
     * @var int $totalCount
     */
    public int $totalCount = 0;

    /**
     * @var float|int $totalAmount
     */
    public float $totalAmount = 0;

    /**
     * @var float|int $discount
     */
    public float $discount = 0;

    /**
     * @var VolumeDiscountRecurring $rebillDiscount
     */
    public ?VolumeDiscountRecurring $rebillDiscount = null;

    /**
     * VolumeDiscountOrderHandler constructor.
     *
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param VolumeDiscountQuantity|null $volumeDiscountQuantity
     * @return $this
     */
    public function setVolumeDiscountQuantity(?VolumeDiscountQuantity $volumeDiscountQuantity): self
    {
        $this->volumeDiscountQuantity = $volumeDiscountQuantity;

        return $this;
    }

    /**
     * @param float $discountAmount
     * @return VolumeDiscountOrderHandler
     */
    public function setDiscountAmount(float $discountAmount): self
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    /**
     * @return float|int
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Save the volume discount pieces with an order.
     */
    public function save(): void
    {
        if ($this->discountId && $this->discountAmount) {
            VolumeDiscountOrderTotal::create([
                'orders_id'  => $this->orderId,
                'value'      => $this->discountAmount,
                'sort_order' => VolumeDiscountOrderTotal::SORT_ORDER,
            ]);

            // Create the volume discount order history note.
            //
            OrderHistoryNote::create([
                'order_id' => $this->orderId,
                'user_id'  => User::SYSTEM,
                'type'     => 'volume-discount-issued',
                'status'   => "{$this->discountId}:{$this->discountAmount}",
            ]);

            // Create the order attribute to relate volume discount ID to the order
            //
            VolumeDiscountOrderAttribute::createForOrder($this->orderId, $this->discountId);
            Log::debug('VD order id: '.$this->orderId.' order total discount: '.$this->discountAmount);
        }
    }

    /**
     * @param $discountId
     * @return $this
     */
    public function setDiscountId(int $discountId): VolumeDiscountOrderHandler
    {
        $this->discountId = $discountId;

        return $this;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function setOrderRebillPrices(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param null $orderId
     * @return array|mixed
     */
    public function getOrderRebillPrice($orderId, $orderType)
    {
        foreach ($this->items as $item) {
            if ($item['order_id'] == $orderId && $item['type_id'] === $orderType) {
                return $item;
            }
        }

        return [];
    }

    /**
     * Save the volume discount pieces for next re-bill (recurring cron or force-bill).
     */
    public function saveRebill(): void
    {
        if ($this->discountId && $this->discountAmount) {
            VolumeDiscountRebillOrderTotal::updateOrCreate([
                'orders_id' => $this->orderId,
                'class'     => VolumeDiscountRebillOrderTotal::CLASS_NAME
            ], [
                    'orders_id'  => $this->orderId,
                    'value'      => $this->discountAmount,
                    'sort_order' => VolumeDiscountRebillOrderTotal::SORT_ORDER,
                ]);
            Log::debug('VD order id: '.$this->orderId.' order total discount: '.$this->discountAmount);
            // Create the order attribute to relate volume discount ID to the order
            //
            if (! $this->rebillDiscount) {
                VolumeDiscountRecurring::createForOrder($this->orderId, $this->discountId);
            }
        }
    }

    /**
     * @param $lineItems
     */
    public function saveOrderRebillPrices($lineItems)
    {
        $this->setOrderRebillPrices($lineItems);
        foreach ($lineItems as $lineItem) {

            //override order type if upsell became main
            if ($this->rebill && $this->rebill == $lineItem['order_id']) {
                $lineItem['type_id'] = ORDER_TYPE_MAIN;
            }

            if ($lineItem['type_id'] === ORDER_TYPE_MAIN) {
                OrderProductVolumeDiscountPrice::updateOrCreate([
                    'orders_id' => $this->orderId,
                    'class'     => OrderProductVolumeDiscountPrice::CLASS_NAME
                ], [
                        'orders_id'  => $this->orderId,
                        'value'      => $lineItem['product_price'],
                        'sort_order' => OrderProductVolumeDiscountPrice::SORT_ORDER,
                    ]);
            } else {
                UpsellProductVolumeDiscountPrice::updateOrCreate([
                    'upsell_orders_id' => $lineItem['order_id'],
                    'class'            => UpsellProductVolumeDiscountPrice::CLASS_NAME
                ], [
                        'upsell_id'  => $lineItem['order_id'],
                        'value'      => $lineItem['product_price'],
                        'sort_order' => UpsellProductVolumeDiscountPrice::SORT_ORDER,
                    ]);
            }
            Log::debug('VD order id: '.$this->orderId.(($lineItem['type_id'] !== ORDER_TYPE_MAIN) ? ' upsell id: '.$lineItem['order_id'] : '').' product price: '.$lineItem['product_price']);
        }
    }

    /**
     * @param int $upsellOrderId
     * @return $this
     */
    public function setExcludeUpsellId($upsellOrderId): VolumeDiscountOrderHandler
    {
        $this->excludeUpsellId = $upsellOrderId;

        return $this;
    }

    /**
     * @return bool
     */
    public function initializeOrder(): bool
    {
        $this->initOrder();
        $this->setVolumeDiscount();
        $this->setProducts();
        if ($this->volumeDiscount && $this->volumeDiscount->applyToRecurringOrders() && $this->lineItems) {
            $this->updateEligibleItems();

            return (bool) count($this->discountPriceMap);
        }

        return false;
    }

    /**
     * @param $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Sets Order
     */
    public function initOrder()
    {
        if ($order = Order::find($this->orderId)) {
            $this->order = $order;
        }
    }

    /**
     * @param Order|OrderReader $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Sets VD products
     */
    public function setProducts()
    {
        if ($this->order) {
            $this->lineItems = $this->order->all_recurring_items;
        }
    }

    /**
     * Sets volume discount vars
     */
    public function setVolumeDiscount()
    {
        if ($this->order) {
            $this->rebillDiscount = VolumeDiscountRecurring::where('order_id', $this->orderId)->first();

            if ($volumeDiscountCampaign = VolumeDiscountCampaign::where('campaign_id', $this->order->campaign_id)->first()) {
                $this->volumeDiscount = $volumeDiscountCampaign->volume_discount;
                if ($this->volumeDiscount) {
                    $this->discountId = $this->volumeDiscount->id;
                }

                // if we had rebill discount we need to remove it for this order
            } else if ($this->rebillDiscount) {
                $this->rebillDiscount->delete();
                $this->removeAllVolumeDiscountPrices();
            }
        }
    }

    /**
     * Recalculate VD and save.
     *
     * @param int $rebill
     * @return VolumeDiscount|null
     */
    public function reCalculateVolumeDiscount($rebill = 0): ?VolumeDiscount
    {
        $removeVolumeDiscount = true;
        $this->rebill         = $rebill;
        if ($this->initializeOrder()) {
            Log::debug('VD order id: '.$this->orderId.' product count: '.$this->totalCount);
            $volumeDiscountQuantity = $this->volumeDiscount->getQuantityByItemCount($this->totalCount);

            if ($this->totalCount && $volumeDiscountQuantity) {
                $this->calculateDiscount($volumeDiscountQuantity);
                $removeVolumeDiscount = false;
                $this->saveRebill();
                $this->saveOrderRebillPrices($this->items);
            }
        }

        if ($this->rebillDiscount && $removeVolumeDiscount) {
            $this->rebillDiscount->delete();
        }

        return $this->volumeDiscount;
    }

    /**
     *
     * @return void
     */
    public function updateEligibleItems()
    {
        if ($this->volumeDiscount && $products = $this->volumeDiscount->products) {
            $mainNextRecurringDate = false;
            $excludeNonRecurring   = $this->volumeDiscount->isExcludeNonRecurring();

            // this is case when we have decline salvaged order and next recurring is now set in date purchased field
            // using zero date logic for default
            //
            if (($recurringDate = $this->lineItems[0]->date_purchased) && $recurringDate->year > 1) {
                $mainNextRecurringDate = $recurringDate->format('Y-m-d');
            } else {
                if ($this->rebill || $this->excludeUpsellId) {
                    $mainNextRecurringDate = Carbon::now()->format('Y-m-d');
                } else {
                    if ($recurringDate = $this->lineItems[0]->recurring_date) {
                        $mainNextRecurringDate = $recurringDate->format('Y-m-d');
                    } else {
                        foreach ($this->lineItems as $key => $item) {
                            if ($recurringDate = $item->recurring_date) {
                                $mainNextRecurringDate = $recurringDate->format('Y-m-d');
                                break;
                            }
                        }
                    }
                }
            }
            Log::debug('VD order id: '.$this->orderId.' main recurring date set: '.$mainNextRecurringDate);

            foreach ($this->lineItems as $key => $item) {
                $subscriptionOrder     = $item->subscription_order;
                $nextRecurringPriceSet = false;
                $preservePriceFlag     = $subscriptionOrder->is_preserve_price;
                $orderInfo             = ($item instanceof Upsell) ?
                                        ' upsell id: '.$item->upsell_orders_id.' upsell subscription id: '.$item->subscription_id
                                        : ' order subscription id: '.$item->subscription_id;

                // only if we have next_recurring_price set it could be: next product changed or custom price set
                // need to determine it here
                //
                if (
                    ($nextRecurringProductId = (int) $subscriptionOrder->next_recurring_product) &&
                    ($product = Product::find($nextRecurringProductId))
                ) {

                    if ($nextRecurringPrice = $subscriptionOrder->next_recurring_price) {
                        $nextRecurringPrice = (float) $nextRecurringPrice;
                        if ($nextRecurringPrice !== (float) $product->products_price || $nextRecurringPrice === 0.00) {
                            $nextRecurringPriceSet = true;
                        } else {
                            if ($nextRecurringPrice === (float) $product->products_price) {
                                $preservePriceFlag = false;
                            }
                        }
                    }

                    // this is case when upsell is in decline salvage as well
                    if (($upsellRecurringDate = $item->date_purchased) && $upsellRecurringDate->year > 1) {
                        $upsellRecurringDate = $upsellRecurringDate->format('Y-m-d');
                    } else {
                        $upsellRecurringDate = $item->recurring_date->format('Y-m-d');
                    }

                    if (
                        ($nextRecurringProductId && ! in_array($nextRecurringProductId,$products)) ||
                        ($item->is_archived || $item->is_hold) ||
                        (
                            $mainNextRecurringDate !== $upsellRecurringDate &&
                            $this->excludeUpsellId !== $item->upsell_orders_id &&
                            $upsellRecurringDate >= Carbon::now()->format('Y-m-d')
                        ) ||
                        ($preservePriceFlag && $nextRecurringPriceSet) ||
                        ($excludeNonRecurring && $subscriptionOrder->bill_by_type_id === 0) ||
                        (float) $product->products_price === 0.00
                    ) {
                        Log::debug('VD order id: ' . $this->orderId . ' unsetting product: ' . $nextRecurringProductId . $orderInfo .
                            ' current recurring date ' . $upsellRecurringDate .
                            ' exclude upsell id ' . $this->excludeUpsellId . ' or next recurring price set: ' . (int) $nextRecurringPriceSet);

                        unset($this->lineItems[$key]);

                        //in case if product not qualify for VD need to remove price calculation record
                        if (! empty($item->upsell_orders_id)) {
                            $this->removeVolumeDiscountPrice($item->upsell_orders_id);
                        } else {
                            $this->removeVolumeDiscountPrice();
                        }
                    } else {
                        $index = $key.'_'.$nextRecurringProductId.'-'.($subscriptionOrder->next_recurring_variant ?? 0).'-'.$subscriptionOrder->frequency_id;
                        [
                            $subtotal,
                            $count,
                            $lineItemCount
                        ] = $this->getProductData($subscriptionOrder, $product);
                        Log::debug('VD order id: '.$this->orderId.' product key: '.$index.' subtotal '.$subtotal.' line-item count '.$lineItemCount.$orderInfo);

                        $this->discountPriceMap[$index] = $subtotal;
                        $this->totalCount              += $count;
                        $this->totalAmount             += $subtotal;
                        $this->items[$index]            = [
                            'subtotal'      => $subtotal,
                            'lineItemCount' => $lineItemCount,
                            'order_id'      => $subscriptionOrder->order_id,
                            'type_id'       => $subscriptionOrder->type_id
                        ];
                    }
                }
            }
        }
    }

    /**
     * @param $volumeDiscountQuantity
     */
    private function calculateDiscount($volumeDiscountQuantity)
    {
        $totalPerPrice  = round(($this->totalAmount / $this->totalCount) * count($this->items), 4);
        $this->discount = $volumeDiscountQuantity->getDiscountFromAmount($totalPerPrice);
        Log::debug('VD order id: '.$this->orderId.' total price: '.$totalPerPrice.' discount: '.$this->discount);
        $discountDistributor = new DiscountDistributor($this->discountPriceMap);
        $discountAmount      = $volumeDiscountQuantity->isDollarAmount() ? 0 : $volumeDiscountQuantity->amount;
        $distributorResult   = $discountDistributor->getDiscountedMap($this->discount, $discountAmount);
        $updatedMap          = $distributorResult->get('map');
        $unitDiscountMap     = $distributorResult->get('unit_discounts');

        foreach ($updatedMap as $key => $discountedPrice) {
            $this->items[$key]['subtotal']      = $discountedPrice;
            $this->items[$key]['product_price'] = $discountedPrice / $this->items[$key]['lineItemCount'];
            Log::debug('VD order id: '.$this->orderId.' product '.$key.' discounted price: '.$this->items[$key]['product_price'].' per product');
            if (isset($unitDiscountMap[$key])) {
                $this->discountAmount += round(($unitDiscountMap[$key]), 4);
            }
        }
    }

    /**
     * @param $subscriptionOrder
     * @param $product
     * @return array
     */
    private function getProductData($subscriptionOrder, $product): array
    {
        $count    = 0;
        $subtotal = 0;
        if ($product) {
            $lineItemCount = 1;
            if ($product->is_custom_bundle) {
                if ($subscriptionOrder) {
                    foreach ($subscriptionOrder->next_bundle_products($this->orderId)->get() as $bundleChild) {
                        $count    += $bundleChild->quantity;
                        $subtotal += $bundleChild->charged_price;
                    }
                }
            } else {
                if ($product->is_prebuilt_bundle) {
                    $count    = $product->calculatedItemCount(1, []);
                    $subtotal = $product->calculatedBundleSubtotal(1, []);
                } else {
                    if ($subscriptionOrder->next_recurring_variant) {
                        $variant = ProductVariant::find($subscriptionOrder->next_recurring_variant);
                        $price   = $variant->getPrice() ?? $product->products_price;
                    } else {
                        $price = (float) $product->products_price;
                    }
                    $count    = $lineItemCount = $subscriptionOrder->next_recurring_quantity;
                    $subtotal = $price * $count;
                }
            }

            if (($discountPercent = (float) $subscriptionOrder->sticky_discount_percent) > 0) {
                $discountAmount = round(($discountPercent / 100) * $subtotal, 2);
                $subtotal       -= $discountAmount;
            } else {
                if (($discountAmount = (float) $subscriptionOrder->sticky_discount_flat_amount) > 0) {
                    $subtotal -= $discountAmount;
                }
            }

            return [$subtotal, $count, $lineItemCount];
        }

        return [0, 0, 0];
    }

    /**
     * Remove all VD calculations from order
     */
    public function removeAllVolumeDiscountPrices()
    {
        $this->setProducts();
        foreach ($this->lineItems as $key => $item) {
            if (! empty($item->upsell_orders_id)) {
                $this->removeVolumeDiscountPrice($item->upsell_orders_id);
            } else {
                $this->removeVolumeDiscountPrice();
            }
        }
    }

    /**
     * @param null $upsellOrderId
     */
    public function removeVolumeDiscountPrice($upsellOrderId = null) :void
    {
        if (!$upsellOrderId) {
            OrderProductVolumeDiscountPrice::where(
                [
                    'orders_id'  => $this->orderId,
                    'class'      => OrderProductVolumeDiscountPrice::CLASS_NAME
                ]
            )->delete();
        } else {
            UpsellProductVolumeDiscountPrice::where(
                [
                    'upsell_orders_id'  => $upsellOrderId,
                    'class'             => UpsellProductVolumeDiscountPrice::CLASS_NAME
                ]
            )->delete();
        }
        Log::debug('VD order id: ' . $this->orderId . ' removing product discounted price for ' .
            ($upsellOrderId ? ' upsell: ' . $upsellOrderId : '')
        );
    }
}
