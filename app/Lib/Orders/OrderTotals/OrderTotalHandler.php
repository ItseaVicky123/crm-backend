<?php

namespace App\Lib\Orders\OrderTotals;

use App\Exceptions\TrialWorkflow\BadWorkflowRelationException;
use App\Exceptions\TrialWorkflow\WorkflowUnitNotFoundException;
use App\Facades\SMC;
use App\Lib\LineItems\DiscountDistributor;
use App\Lib\Orders\OrderTotals\ModuleRequests\OrderTotalRequest;
use App\Models\BillingModel\BillingModel;
use App\Models\Country;
use App\Models\Offer\Offer;
use App\Models\OrderHistoryNote;
use App\Models\OrderLineItems\VolumeDiscount as VolumeDiscountOrderTotal;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use App\Lib\TrialWorkflow\OrderProcess\InitialHandler as TrialWorkflowHandler;
use fileLogger AS Log;
use system_module_control;

/**
 * Class OrderTotalHandler
 * @package App\Lib\Orders\OrderTotals
 */
class OrderTotalHandler
{
    /**
     * @var OrderTotalRequest $request
     */
    protected OrderTotalRequest $request;

    /**
     * @var int|null $campaignId
     */
    protected ?int $campaignId = null;

    /**
     * @var bool
     */
    protected bool $isDiscountCalculated = false;

    /**
     * OrderTotalHandler constructor.
     * @param OrderTotalRequest $request
     */
    public function __construct(OrderTotalRequest $request)
    {
        $this->request    = $request;
        $this->campaignId = $this->request->get('campaign_id');
    }

    /**
    * @param bool $calculated
    * @return $this
    */
    public function setIsDiscountCalculated(bool $calculated)
    {
        $this->isDiscountCalculated = $calculated;
        return $this;
    }

    /**
    * @param int $orderId
    * @return float
    */
    private function calculateVolumeDiscount(int $orderId): float
    {
        if ($volumeDiscount = VolumeDiscountOrderTotal::where('order_id', $orderId)->where('class', 'ot_volume_discount')->first()) {
            return (float) $volumeDiscount->value;
        }

        return 0.0;
    }


     /**
     * Calculate the order total.
     * @return Collection
     */
    public function calculate(): Collection
    {
        // Generate the line item list.
        //
        $productList = !$this->request->isLegacyClient() ? $this->processItemizedList() : $this->processLegacyItemizedList();

        // Inject a volume discount if exists.
        //
        if (system_module_control::check(SMC::VOLUME_DISCOUNTS)) {
            if($this->isDiscountCalculated) {
                $volumeDiscountAmount = $this->calculateVolumeDiscount($this->request->orderId);
            } else {
                $volumeDiscountAmount = $this->applyVolumeDiscount($productList);
            }
        }


        $productListArray = $productList->toArray();
        $locationData     = [];
        $useTaxProvider   = $this->request->get('use_tax_provider', 0);
        $location         = collect($this->request->get('location', []));
        $billinglocation  = collect($this->request->get('billingLocation', []));
        $promo            = collect($this->request->get('promo', []));
        $lineItems        = collect();
        $discounts        = collect();
        $firstProduct     = $productList->first();
        $trialWorkflowId  = $firstProduct['trial_workflow_id'] ?? 0;

        if ($useTaxProvider >= 0) {
            // Shipping
            $postalCode = $location->get('postal_code');
            $state      = $location->get('state');
            $country    = Country::where('iso_2', $location->get('country'))->first();

            // Billing, default to shipping
            $billingZip     = $billinglocation->get('postal_code', $postalCode);
            $billingState   = $billinglocation->get('state', $state);
            $billingCountry = $country;

            if (! empty($billinglocation->get('country'))) {
                $billingCountry = Country::where('iso_2', $billinglocation->get('country'))->first();
            }

            $locationData = [
                'shipZip'       => $postalCode,
                'shipStateId'   => $state,
                'shipCountryId' => $country->id,

                'billZip'       => $billingZip,
                'billStateId'   => $billingState,
                'billCountryId' => $billingCountry->id,

                'shipAddress'   => '',
                'shipCity'      => '',
            ];
        }

        $calculated = \GetTaxTotal(
            $productList->toArray(),
            $this->campaignId,
            (int) $this->request->get('shipping_id', 0),
            $locationData,
            $promo->get('code'),
            $promo->get('email'),
            $useTaxProvider,
            $trialWorkflowId
        );

        // Product line items
        foreach ($calculated['product_payload'] as $lineItem) {
            $id           = explode('|', $lineItem['id']); // remove iterationCount from the id before return
            $productPrice = $productListArray[$lineItem['id']]['product_price'];
            $variantPrice = $productListArray[$lineItem['id']]['variant_price'];
            $variantId    = $productListArray[$lineItem['id']]['variant_id'] ?? null;
            $item         = [
                'id'            => $id[1] ?? $id[0],
                'name'          => $lineItem['name'],
                'qty'           => $lineItem['quantity'] + ($lineItem['quantity_bogo'] ?? 0),
                'base_price'    => $lineItem['base_price_raw'],
                'unit_price'    => $lineItem['base_price_raw'],
                'product_price' => $productPrice,
                'variant_price' => number_format($variantPrice, 2, '.', ''),
                'total'         => number_format($lineItem['summary_price_raw'], 2, '.', ''),
                'is_taxable'    => (bool) $productListArray[$lineItem['id']]['tax'],
                'is_shippable'  => (bool) $productListArray[$lineItem['id']]['ship'],
                'is_prepaid'    => (bool) $productListArray[$lineItem['id']]['prepaid'],
                'variant_id'    => $variantId,
            ];

            if ($item['is_prepaid']) {
                $item['prepaid'] = $productListArray[$lineItem['id']]['prepaid'];
            }

            if ($lineItem['discount']) {
                $item['base_price'] = $lineItem['discount']['base'];
                $discountTotal      = ($item['base_price'] - $item['unit_price']) * $lineItem['quantity'];
                $item['discount']   = [
                    'percent' => $lineItem['discount']['percent'],
                    'total'   => $discountTotal,
                ];
                $discounts->push([
                    'type'       => 'subscription',
                    'product_id' => $item['id'],
                    'total'      => $discountTotal,
                ]);
            }

            // Format at the end after these values are used for calculations because
            // formatted numbers break aggregations.
            //
            $item['base_price']    = number_format($item['base_price'], 2, '.', '');
            $item['unit_price']    = number_format($item['unit_price'], 2, '.', '');
            $item['product_price'] = number_format($item['product_price'], 2, '.', '');

            $lineItems->push($item);
        }

        // If a promo code was sent, collect discount info
        //
        if ($calculated['is_bxgy_applied'] || $promo->count()) {
            $discounts->push([
                'type'                   => $promo->count() ? 'promo' : 'bxgy',
                'code'                   => $promo->get('code'),
                'valid'                  => $calculated['couponState'] == 1,
                'error'                  => str_replace('Coupon Error: ', '', $calculated['coupon_error']),
                'total'                  => number_format($calculated['coupon_amt_base'], 2,'.',''),
                'is_buy_x_get_y_applied' => $calculated['is_bxgy_applied'],
            ]);
        }
        if ($volumeDiscountAmount > 0) {
            $discounts->push([
                'type'  => 'volume_discount',
                'code'  => null,
                'valid' => true,
                'error' => null,
                'total' => round($volumeDiscountAmount, 2),
            ]);
        }

        // Base response
        //
        $return = collect([
            'total'      => filter_var($calculated['total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'subtotal'   => filter_var($calculated['subTotal'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'line_items' => $lineItems->toArray(),
        ]);

        // Add shipping cost, if applicable
        //
        if ($calculated['shipping'] > 0) {
            $return->put('shipping', $calculated['shipping']);
        }

        // If tax was applied, add to response
        //
        if ($calculated['taxFactor'] > 0) {
            $return->put('tax', [
                'pct'   => number_format($calculated['salesTax'], 2),
                'total' => number_format($calculated['taxFactor'], 2),
            ]);
        }

        // Report any discounts
        //
        if ($discounts->count()) {
            $return->put('discounts', $discounts->toArray());
        }

        return $return;
    }

    /**
     * Get the itemized product list with line-item discounts precalculated.
     * @return Collection
     */
    protected function processItemizedList(): Collection
    {
        $productList = collect();

        if ($offers = $this->request->getAsCollection('offers')) {
            $iterationCount = 0;

            $offers->each(function ($item) use (&$productList, &$iterationCount) {
                $itemCollection = collect($item);
                $offer          = Offer::findOrFail($itemCollection->get('id'));
                $product        = Product::findOrFail($itemCollection->get('product_id'));
                $frequency      = BillingModel::findOrFail($itemCollection->get('billing_model_id'));
                $isRecurring    = $frequency->bill_by_type_id > 0;
                $prepaid        = null;
                $price          = $product->price;
                $productPrice   = $price;
                $variantId      = null;
                $variantPrice   = null;
                $quantity       = $itemCollection->get('quantity', 1);
                $bundleChildren = $itemCollection->get('children', []);

                if ($itemCollection->has('variant_id')) {
                    $variantId = $itemCollection->get('variant_id');
                } else if ($itemCollection->has('variant')) {
                    // The legacy way of getting variant from attributes didnt want to rewrite for now
                    //
                    $productVariant     = new \ProductVariant();
                    $attributeMap       = [];
                    $variantRequestData = $itemCollection->get('variant');

                    foreach ($variantRequestData as $attribute) {
                        $attributeMap[$attribute['attribute_name']] = $attribute['attribute_value'];
                    }

                    $variantId = $productVariant->ValidateVariantAttributes([
                        $itemCollection->get('product_id') => $attributeMap],
                        $itemCollection->get('product_id'),
                        true
                    );
                }

                $discount = [
                    'has_discount' => 0,
                    'base'         => $price,
                ];
                $trialPrice      = null;
                $trialWorkflowId = 0;

                if ((bool) $itemCollection->get('trial', false)) {
                    if ($itemCollection->get('use_workflow', false)) {
                        try {
                            $trialWorkflowData = $this->getTrialWorkflowUnitPrice($itemCollection, $offer);
                            $trialPrice      = $trialWorkflowData->get('price');
                            $trialWorkflowId = $trialWorkflowData->get('trial_workflow_id', 0);
                        } catch (BadWorkflowRelationException $e) {
                            Log::track(__METHOD__, "Trial workflow price could not be calculated [BadWorkflowRelationException: {$e->getMessage()}]", LOG_WARN);
                        } catch (WorkflowUnitNotFoundException $e) {
                            Log::track(__METHOD__, "Trial workflow price could not be calculated [WorkflowUnitNotFoundException: {$e->getMessage()}]", LOG_WARN);
                        }
                    } else if ($offer->trial_price_flag) {
                        // Custom trial price - legacy trial
                        //
                        $trialPrice = $offer->trial_price;
                    }
                }

                if ($itemCollection->has('price')) {
                    // If there is a custom price this takes precedence over configurations.
                    // if quantity more then one and prepaid - it means total price is multiplied
                    //
                    if (($itemQuantity = (int) $itemCollection->get('quantity')) > 1 && $offer->is_prepaid) {
                       $price = $productPrice = $discount['base'] = $itemCollection->get('price') / $itemQuantity;
                    } else {
                       $price = $productPrice = $discount['base'] = $itemCollection->get('price');
                    }

                    if ($itemCollection->has('cycles') && $offer->is_prepaid) {
                        $prepaid = $this->applyPrepaidCycle($itemCollection, $offer, $price);
                        $price   = $prepaid['total'];
                    }
                } else if (!is_null($trialPrice)) {
                    // If there is a trial and a trial price was configured then use this over other configurations.
                    //
                    $price = $trialPrice;
                } else if (!is_null($variantId)) {
                    // If variant ID was passed in then calculate the variant price.
                    //
                    $variant      = ProductVariant::findOrFail($variantId);
                    $price        = $variant->getPrice();
                    $variantPrice = $price;

                    if ($itemCollection->has('cycles') && $offer->is_prepaid) {
                        $prepaid = $this->applyPrepaidCycle($itemCollection, $offer, $price);
                        $price   = $variantPrice = $prepaid['total'];
                    }
                } else if ($itemCollection->has('cycles') && $offer->is_prepaid) {
                    // If it is a prepaid offer and cycles are requested,
                    // perform the prepaid price calculation.
                    //
                    $prepaid = $this->applyPrepaidCycle($itemCollection, $offer, $price);
                    $price   = $prepaid['total'];
                } else if ($product->is_bundle) {
                    // If the product is a bundle, get the bundle subtotal
                    //
                    $price            = $product->calculatedBundleSubtotal(1, $bundleChildren);
                    $discount['base'] = $price;
                    $productPrice     = $price;
                }

                // Billing model subscription discount calculation
                //
                if ((! (bool) $itemCollection->get('trial', false)) && $billingModelConfiguration = $offer->findAssociatedBillingModel($frequency->id)) {
                    if ($billingModelDiscount = $billingModelConfiguration->discount) {
                        $billingModelDiscountCalculator = new SubscriptionDiscountCalculator($billingModelDiscount);

                        if ($billingModelDiscountCalculator->isHasDiscount()) {
                            $discount['has_discount'] = 1;
                            $discount['percent']      = $billingModelDiscountCalculator->getDiscountPercent();
                            $discount['flat_amount']  = $billingModelDiscountCalculator->getDiscountAmount();

                            $price = $billingModelDiscountCalculator->calculatedUnitPrice($product, $variantId, $price);
                        }
                    }
                }

                $variantSuffix  = $variantId ? "-{$variantId}" : '';
                $productListKey = "{$iterationCount}|{$product->id}{$variantSuffix}";
                $productList->put($productListKey, [
                    'price'             => $price,
                    'qty'               => $quantity,
                    'tax'               => $product->is_taxable,
                    'ship'              => $product->is_shippable,
                    'name'              => $product->name,
                    'prepaid'           => $prepaid,
                    'discount'          => $discount,
                    'product_price'     => $productPrice,
                    'variant_price'     => $variantPrice ?? $productPrice,
                    'variant_id'        => $variantId ?? 0,
                    'id'                => $product->id,
                    'item_count'        => $product->calculatedItemCount($quantity, $bundleChildren),
                    'trial_workflow_id' => $trialWorkflowId,
                    'is_recurring'      => $isRecurring,
                ]);
                $iterationCount++;
            });
        }

        return $productList;
    }

    /**
     * Apply prepaid cycle pricing on lineitem.
     *
     * @param Collection $itemCollection
     * @param Offer $offer
     * @param float|null $price
     * @return array
     */
    protected function applyPrepaidCycle($itemCollection, $offer, $price): array
    {
        $prepaidCycles      = $itemCollection->get('cycles');
        $cycleDepth         = $itemCollection->get('cycle_depth', 0);
        $prepaidProfile     = $offer->prepaid_profile;
        $prepaidPriceResult = $prepaidProfile->calculatePrice($price, $prepaidCycles, $cycleDepth);

        return [
            'term'                => $prepaidCycles,
            'base'                => $price,
            'total'               => $prepaidPriceResult->get('total'),
            'subtotal'            => $prepaidPriceResult->get('subtotal'),
            'discount'            => $prepaidPriceResult->get('discount'),
            'is_prepaid_shipping' => $prepaidProfile->is_prepaid_shipping,
        ];
    }

    /**
     * Get the Legacy (NUTRA) itemized product list with line-item discounts precalculated.
     * @return Collection
     */
    protected function processLegacyItemizedList(): Collection
    {
        $productList = collect();
        $products    = collect($this->request->products);
        $products->each(function ($prod) use (&$productList) {
            $item         = collect($prod);
            $product      = Product::findOrFail($item->get('id'));
            $price        = $product->price;
            $productPrice = $price;
            $variantPrice = null;
            $quantity     = $item->get('quantity', 1);

            if ($item->has('price')) {
                // Custom price passed with product
                //
                $price = $item->get('price');
            } else if ($item->has('variant_id')) {
                $variant      = ProductVariant::findOrFail($item->get('variant_id'));
                $price        = $variant->getPrice();
                $variantPrice = $price;
            }

            $productList->put($product->id, [
                'price'         => $price,
                'qty'           => $quantity,
                'tax'           => $product->is_taxable,
                'ship'          => $product->is_shippable,
                'name'          => $product->name,
                'prepaid'       => null,
                'product_price' => $productPrice,
                'variant_price' => $variantPrice ?? $productPrice,
                'discount'      => [
                    'has_discount' => 0,
                    'base'         => $price,
                ],
                'item_count'    => $quantity,
                // added to get product's id using id key,
                // to solve tax issue when using products array in order total calculate API
                'id'            => $product->id,
            ]);
        });

        return $productList;
    }

    /**
     * Get the derived trial workflow unit price if applicable.
     * @param Collection $itemCollection
     * @param Offer $offer
     * @return Collection
     * @throws BadWorkflowRelationException
     * @throws WorkflowUnitNotFoundException
     */
    private function getTrialWorkflowUnitPrice(Collection $itemCollection, Offer $offer): ?Collection
    {
        $data = collect();

        // Trial Workflow
        // Users can pass a trial workflow ID or use the default.
        //
        if ($itemCollection->get('use_workflow', false)) {
            $trialWorkflowHandler = new TrialWorkflowHandler(
                $offer->id,
                $itemCollection->get('trial_workflow_id', 0)
            );
            $data->put('trial_workflow_id', $trialWorkflowHandler->getTrialWorkflowId());

            if ($trialWorkflowHandler->isCurrentUnitValid()) {
                $currentWorkflowUnit = $trialWorkflowHandler->getCurrentUnit();

                if ($currentWorkflowUnit->isPriceSet()) {
                    $data->put('price', $currentWorkflowUnit->price);
                }
            }
        }

        return $data;
    }

    /**
     * Implement volume discounts into the product list.
     * @param Collection $productList
     * @return float
     */
    private function applyVolumeDiscount(Collection &$productList): float
    {
        $volumeDiscountId           = $this->request->get('volume_discount_id', 0);
        $volumeDiscountCalculator   = new VolumeDiscountCalculator($this->campaignId, $volumeDiscountId);
        $volumeDiscountAmount       = 0;
        $eligibleLineItemCollection = $volumeDiscountCalculator->getEligibleProductListCollection(clone $productList);
        $totalItemCount             = $this->getTotalItemCount($eligibleLineItemCollection);
        if ($volumeDiscountQuantity = $volumeDiscountCalculator->getVolumeDiscountQuantity($eligibleLineItemCollection)) {
            $productPriceMap     = [];
            $lineItemTotalAmount = 0;

            foreach ($eligibleLineItemCollection as $listKey => $item) {
                if ($item['qty'] != $item['item_count']) {
                    $currentProductPrice = (float) ($item['price'] / ($item['item_count'] / (int) $item['qty']));
                } else {
                    $currentProductPrice = (float) $item['price'];
                }

                $productPriceMap[$listKey] = $currentProductPrice;
                $lineItemTotalAmount += (float) $item['price'] * $item['qty'];
            }
            $totalPerPrice       = round(($lineItemTotalAmount / $totalItemCount) * count($productPriceMap), 4);
            $discountAmount      = $volumeDiscountQuantity->getDiscountFromAmount($totalPerPrice);
            $discountDistributor = new DiscountDistributor($productPriceMap);
            $percentDiscount     = $volumeDiscountQuantity->isDollarAmount() ? 0 : $volumeDiscountQuantity->amount;
            $distributorResult   = $discountDistributor->getDiscountedMap($discountAmount, $percentDiscount);
            $discountedMap       = $distributorResult->get('map');
            $unitDiscountMap     = $distributorResult->get('unit_discounts');

            foreach ($discountedMap as $mapKey => $discountedPrice) {
                $item = $productList->get($mapKey);
                if ($item['qty'] != $item['item_count']) {
                    $discountedPrice *= $item['item_count'];
                }
                $item['price'] = $discountedPrice;
                $productList->put($mapKey, $item);

                if (isset($unitDiscountMap[$mapKey])) {
                    $volumeDiscountAmount += round(($unitDiscountMap[$mapKey] * $this->getTotalItemCount([$item])), 4);
                }
            }
        }

        return $volumeDiscountAmount;
    }

    /**
     * @param $eligibleLineItemCollection
     * @return int
     */
    private function getTotalItemCount($eligibleLineItemCollection): int
    {
        $totalCount = 0;
        foreach ($eligibleLineItemCollection as $item) {
            if ($item['qty'] != $item['item_count']) {
                $totalCount += $item['item_count'] / (int) $item['qty'];
            } else {
                $totalCount += $item['item_count'];
            }
        }
        return $totalCount;
    }
}
