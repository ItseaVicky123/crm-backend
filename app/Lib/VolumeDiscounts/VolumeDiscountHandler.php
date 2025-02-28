<?php

namespace App\Lib\VolumeDiscounts;

use App\Exceptions\VolumeDiscounts\MaxVolumeDiscountQuantitiesException;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Lib\VolumeDiscounts\ModuleRequests\SaveRequest;
use App\Lib\VolumeDiscounts\ModuleRequests\UpdateRequest;
use App\Lib\VolumeDiscounts\ModuleRequests\AttachToCampaignRequest;
use App\Lib\VolumeDiscounts\ModuleRequests\VolumeDiscountRequest;
use App\Lib\VolumeDiscounts\ModuleRequests\DetachFromCampaignRequest;
use App\Models\Product;
use App\Models\VolumeDiscounts\VolumeDiscount;
use App\Models\VolumeDiscounts\VolumeDiscountCampaign;
use App\Models\VolumeDiscounts\VolumeDiscountProduct;
use App\Models\VolumeDiscounts\VolumeDiscountQuantity;
use Illuminate\Support\Facades\DB;
use App\Exceptions\VolumeDiscounts\InvalidQuantityBoundsException;

/**
 * Class VolumeDiscountHandler
 * @package App\Lib\VolumeDiscounts
 */
class VolumeDiscountHandler
{
    /**
     * Create a volume discount with a pre-validated request.
     * @param SaveRequest $request
     * @return VolumeDiscount
     * @throws InvalidQuantityBoundsException
     */
    public function create(SaveRequest $request): VolumeDiscount
    {
        if ( ($applyToTypeId = (int) $request->get('apply_to_type_id', 1)) && ! $this->isApplyToTypeIdCorrect($applyToTypeId)) {
            $applyToTypeId = 1;
        }

        $resource = VolumeDiscount::create([
            'name'                     => $request->name,
            'description'              => $request->description,
            'is_active'                => $request->get('is_active', 1),
            'is_exclude_non_recurring' => $request->get('is_exclude_non_recurring', 0),
            'apply_to_type_id'         => $applyToTypeId,
        ]);
        $this->validateQuantityBounds($request->quantities);

        foreach ($request->quantities as $quantity) {
            $resource
                ->quantities()
                ->create($quantity);
        }

        foreach ($request->get('products') as $productId) {
            if (Product::find($productId)) {
                VolumeDiscountProduct::create([
                    'product_id'         => $productId,
                    'volume_discount_id' => $resource->id
                ]);
            }

        }

        $resource->refresh();

        return $resource;
    }

    /**
     * Updated a volume discount with a pre-validated request.
     * @param UpdateRequest $request
     * @return VolumeDiscount
     * @throws InvalidQuantityBoundsException
     * @throws MaxVolumeDiscountQuantitiesException
     */
    public function update(UpdateRequest $request): VolumeDiscount
    {
        $resource = $request->getVolumeDiscount();
        $updates  = $request->getWhereExists([
            'name',
            'description',
            'is_active',
            'is_exclude_non_recurring',
        ]);

        if ( $request->has('apply_to_type_id') && ($applyToTypeId = (int) $request->get('apply_to_type_id', 1))) {
            if ($this->isApplyToTypeIdCorrect($applyToTypeId)) {
                $updates['apply_to_type_id'] = $applyToTypeId;
            } else {
                $updates['apply_to_type_id'] = 1;
            }
        }

        if ($updates) {
            $resource->update($updates);
        }

        if ($request->has('quantities')) {
            $totalQuantities = [];

            // If the user wants to replace all quantities then wipe the existing child records first.
            //
            if ($request->get('is_replace', 0)) {
                $resource
                    ->quantities()
                    ->delete();
            } else {
                foreach ($resource->quantities as $resourceQuantity) {
                    $totalQuantities[] = $resourceQuantity->toArray();
                }
            }

            // Fetch the new quantities only to ensure the user doesn't go over the limit.
            //
            foreach ($request->quantities as $quantity) {
                if (!isset($quantity['id'])) {
                    $totalQuantities[] = $quantity;
                }
            }

            // After new quantities have been appended, check for maximum allowed
            //
            if (count($totalQuantities) > VolumeDiscount::maxQuantitiesAllowed()) {
                throw new MaxVolumeDiscountQuantitiesException;
            }

            $this->validateQuantityBounds($totalQuantities);

            foreach ($request->quantities as $quantity) {
                $quantityRequest = new ModuleRequest($quantity);

                // If the quantity ID exists then update the existing child record.
                //
                if ($quantityRequest->has('id')) {
                    $quantityModel   = VolumeDiscountQuantity::find($quantityRequest->id);
                    $quantityUpdates = $quantityRequest->getWhereExists([
                        'lower_bound',
                        'upper_bound',
                        'discount_type_id',
                        'amount',
                    ]);

                    if ($quantityUpdates && $quantityModel) {
                        $quantityModel->update($quantityUpdates);
                    } else {
                        $resource
                            ->quantities()
                            ->create($quantityRequest->getWhereExists([
                                'lower_bound',
                                'upper_bound',
                                'discount_type_id',
                                'amount',
                            ]));
                    }
                } else {
                    $resource
                        ->quantities()
                        ->create($quantityRequest->getWhereExists([
                            'lower_bound',
                            'upper_bound',
                            'discount_type_id',
                            'amount',
                        ]));
                }
            }
        }

        if ($request->get('is_replace_products', 0)) {
            $resource
                ->volume_discount_products()
                ->delete();
        }

        if ($request->has('products') && is_array($request->get('products'))) {
            foreach ($request->get('products') as $productId) {
                if (Product::find($productId)) {
                    VolumeDiscountProduct::updateOrCreate([
                        'product_id'         => $productId,
                        'volume_discount_id' => $resource->id
                    ]);
                }

            }
        }

        $resource->refresh();

        return $resource;
    }

    /**
     * @param int $applyToTypeId
     * @return bool
     */
    public function isApplyToTypeIdCorrect(int $applyToTypeId): bool
    {
        return in_array($applyToTypeId, [
            VolumeDiscount::APPLY_TO_RECURRING,
            VolumeDiscount::APPLY_TO_BOTH,
            VolumeDiscount::APPLY_TO_INITIAL,
        ]);
    }

    /**
     * Copy an existing volume discount.
     * @param VolumeDiscountRequest $request
     * @return VolumeDiscount
     */
    public function copy(VolumeDiscountRequest $request): VolumeDiscount
    {
        $resource = $request->getVolumeDiscount();
        $copy     = VolumeDiscount::create([
            'name'                     => $resource->name . ' (Copy)',
            'description'              => $resource->description,
            'is_active'                => 1,
            'is_exclude_non_recurring' => $resource->is_exclude_non_recurring,
            'apply_to_type_id'         => $resource->apply_to_type_id,
        ]);

        if (($quantities = $resource->quantities) && $quantities->isNotEmpty()) {
            foreach ($quantities as $quantity) {
                $copy->quantities()
                    ->create([
                        'lower_bound'      => $quantity->lower_bound,
                        'upper_bound'      => $quantity->upper_bound,
                        'discount_type_id' => $quantity->discount_type_id,
                        'amount'           => $quantity->amount,
                    ]);
            }
        }

        $copy->refresh();

        return $copy;
    }

    /**
     * Delete a volume discount.
     * @param VolumeDiscountRequest $request
     * @return bool
     * @throws \Exception
     */
    public function destroy(VolumeDiscountRequest $request): bool
    {
        return (bool) $request->getVolumeDiscount()->delete();
    }

    /**
     * Attach a volume discount to one or more campaigns.
     * @param AttachToCampaignRequest $request
     * @return bool
     */
    public function relateCampaign(AttachToCampaignRequest $request): bool
    {
        $resource       = $request->getVolumeDiscount();
        $isAllCampaigns = (bool) $request->get('is_all_campaigns', false);
        $isReplace      = (bool) $request->get('is_replace', false);

        if ($isReplace) {
            VolumeDiscountCampaign::where('volume_discount_id', $resource->id)->delete();
            $resource->refresh();
        }

        if ($isAllCampaigns) {
            VolumeDiscountCampaign::where('volume_discount_id', '>', 0)->delete();
            // Using Raw sql here because the user wants to attach a volume discount to all campaigns.
            // This is the most efficient way to attach all campaigns
            // without potentially looping through thousands of campaign objects.
            //
            DB::statement('
                INSERT INTO volume_discount_campaigns 
                    (campaign_id, volume_discount_id) 
                SELECT 
                      c.c_id, 
                           ? 
                       FROM 
                           campaigns AS c 
                      WHERE 
                           c.archived_flag = 0
            ', [$resource->id]);
        } else if ($campaigns = $request->get('campaigns', [])) {
            foreach ($campaigns as $campaign) {
                VolumeDiscountCampaign::where('campaign_id', $campaign['id'])->delete();
                VolumeDiscountCampaign::create([
                    'campaign_id'        => $campaign['id'],
                    'volume_discount_id' => $resource->id,
                ]);
            }
        }

        return (bool) $resource->refresh();
    }

    /**
     * Detach a volume discount from one or more campaigns.
     * @param DetachFromCampaignRequest $request
     * @return bool
     */
    public function unrelateCampaign(DetachFromCampaignRequest $request): bool
    {
        $resource       = $request->getVolumeDiscount();
        $isAllCampaigns = (bool) $request->get('is_all_campaigns', false);

        // Detach from all campaigns in a batch delete
        //
        if ($isAllCampaigns) {
            VolumeDiscountCampaign::where('volume_discount_id', $resource->id)->delete();
        }  else if ($campaigns = $request->get('campaigns', [])) {
            foreach ($campaigns as $campaign) {
                VolumeDiscountCampaign::where([
                    ['campaign_id', $campaign['id']],
                    ['volume_discount_id', $resource->id],
                ])->delete();
            }
        }

        return (bool) $resource->refresh();
    }

    /**
     * Ensure the volume discount quantity bounds are valid.
     * @param array $quantities
     * @throws InvalidQuantityBoundsException
     */
    private function validateQuantityBounds(array $quantities): void
    {
        $ranges  = [];

        foreach ($quantities as $quantity) {
            $lowerBound = $quantity['lower_bound'];
            $upperBound = isset($quantity['upper_bound']) ? $quantity['upper_bound'] : null;

            // The upper bound must be greater if specified.
            //
            if (!is_null($upperBound) && $lowerBound >= $upperBound) {
                throw new InvalidQuantityBoundsException;
            }

            // Check current quantity against existing validated ranges.
            //
            foreach ($ranges as $range) {
                $rangeLowerBound = $range['lower_bound'];
                $rangeUpperBound = isset($range['upper_bound']) ? $range['upper_bound'] : null;

                // If there is a range with no upper bound and the lower or bound we are validating is higher than that
                // then it is not a valid range.
                //
                if (is_null($rangeUpperBound)) {
                    if ($lowerBound >= $rangeLowerBound) {
                        throw new InvalidQuantityBoundsException;
                    }

                    if (!is_null($upperBound) && ($upperBound >= $rangeLowerBound)) {
                        throw new InvalidQuantityBoundsException;
                    }
                } else {
                    // Reject if the lower bound lies between an existing range
                    //
                    if ($lowerBound >= $rangeLowerBound && $lowerBound <= $rangeUpperBound) {
                        throw new InvalidQuantityBoundsException;
                    }

                    if (!is_null($upperBound)) {
                        if ($upperBound >= $rangeLowerBound && $upperBound <= $rangeUpperBound) {
                            throw new InvalidQuantityBoundsException;
                        }
                    }
                }

            }

            // If we made it this far the range must be valid
            //
            $ranges[] = $quantity;
        }
    }
}
