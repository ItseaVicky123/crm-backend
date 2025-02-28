<?php

namespace App\Lib\Offer;

use App\Models\BillingModel\Template;
use App\Models\Offer\Type as OfferType;
use App\Models\Offer\BillingModelDiscount;
use Illuminate\Support\Facades\DB;
use App\Models\Offer\Offer;
use App\Models\Offer\CollectionOfferProduct;
use billing_models\exception;

/**
 * Main handler for copying a Offer.
 * Class CopyHandler
 * @package App\Lib\Offer
 */
class CopyHandler
{
    /**
     * @var Offer|null $copy
     */
    protected ?Offer $copy = null;

    /**
     * @var int|null $newOfferId
     */
    protected ?int $newOfferId = null;

    /**
     * @var int|null $newCOId
     */
    protected ?int $newCOId = null;

    /**
     * @var int|null $oldCOId
     */
    protected ?int $oldCOId = null;

    /**
     * @var Offer|null $original
     */
    protected ?Offer $original = null;

    /**
     * @var int|null $originalOfferId
     */
    protected ?int $originalOfferId = null;

    /**
     * @var int|null $newTemplateId
     */
    protected ?int $newTemplateId = null;

    /**
     * CopyHandler constructor.
     * @param int $originalOfferId
     * @throws \Exception
     */
    public function __construct(int $originalOfferId)
    {
        if (!($this->originalOfferId = $originalOfferId)) {
            throw new exception(exception::ERR_PARAMS, __CLASS__, __METHOD__);
        }
    }

    /**
     * Perform the copy operation.
     * @return int
     * @throws \Exception
     */
    public function performCopy(): int
    {
        $newOfferId = 0;

        try {
            // Load up the target Offer instance
            //
            $this->original = Offer::find($this->originalOfferId);
            if (!$this->original) {
                throw new exception(exception::ERR_DATA_ID, __CLASS__, __METHOD__);
            }
            // There are many pieces, make sure we roll back if things don't go as planned
            //
            DB::beginTransaction();

            if ($this->copyTemplate() && $this->copy = $this->copyWithNoRelations()) {
                $this->copyBillingModels();

                if ($this->original->type_id == OfferType::TYPE_COLLECTION) {
                    $this->copyCollectionOffers();
                    $this->copyCollectionOfferProducts();
                    $this->copyBillingModelDiscount();
                }
                $newOfferId = $this->newOfferId;
            } else {
                throw new exception(exception::ERR_COPY, __CLASS__, __METHOD__);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $newOfferId;
    }

    /**
     * @return Template
     */
    private function copyTemplate(): Template
    {
        $newTemplate = $this->original->billing_template->replicate();
        $this->newTemplateId = $newTemplate->id;

        return $newTemplate;
    }

    /**
     * Copy the base columns in the Offers table to a new Offer record.
     * @return Offer
     */
    private function copyWithNoRelations(): Offer
    {
        $newOffer              = $this->original->replicate();
        $newOffer->name        = $this->original->name . ' (COPY)';
        $newOffer->template_id = $this->newTemplateId;
        $newOffer->save();
        $this->newOfferId = $newOffer->id;

        return $newOffer;
    }

    /**
     * Copy collection_offers
     */
    private function copyCollectionOffers(): void
    {
        if ($this->copy && $this->original) {
            $newCollectionOffer = $this->original->offer_details->replicate();
            $newCollectionOffer->offer_id = $this->newOfferId;
            $newCollectionOffer->save();
            $this->newCOId = $newCollectionOffer->id;
            $this->oldCOId = $this->original->offer_details->id;
        }
    }

    /**
     * Copy collection_offers
     */
    private function copyCollectionOfferProducts(): void
    {
        if ($this->copy && $this->original) {
            $collectionOfferProducts = CollectionOfferProduct::where('collection_offer_id', $this->oldCOId)->get();
            foreach ($collectionOfferProducts as $collectionOfferProduct) {
                $newCollectionOfferProduct = $collectionOfferProduct->replicate();
                $newCollectionOfferProduct->collection_offer_id = $this->newCOId;
                $newCollectionOfferProduct->save();
            }
        }
    }

    /**
     * Copy collection_offers
     */
    private function copyOfferSupplimentalProducts(): void
    {
        if ($this->copy && $this->original) {
            $offerSupplimentalProducts = OfferSupplementalProduct::where('offer_id', $this->original->id)->get();
            foreach ($offerSupplimentalProducts as $offerSupplimentalProduct) {
                $newOfferSupplimentalProduct = $offerSupplimentalProduct->replicate();
                $newOfferSupplimentalProduct->offer_id = $this->newOfferId;
                $newOfferSupplimentalProduct->save();
            }
        }
    }

    /**
     * Copy billing_offer_frequency_discount
     */
    private function copyBillingModelDiscount(): void
    {
        if ($this->copy && $this->original) {
            $billingModelDiscounts = BillingModelDiscount::where('offer_id', $this->original->id)->get();
            foreach ($billingModelDiscounts as $billingModelDiscount) {
                $newBillingModelDiscount = $billingModelDiscount->replicate();
                $newBillingModelDiscount->offer_id = $this->newOfferId;
                $newBillingModelDiscount->save();
            }
        }
    }

    /**
     * Copy billing_subscription_frequency
     */
    private function copyBillingModels(): void
    {
        if ($this->copy && $this->original) {
            $billingModelIds = $this->original->billing_models()->pluck('frequency_id')->all();
            $this->copy->billingFrequencies()->attach($billingModelIds);
        }
    }
}
