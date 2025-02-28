<?php


namespace App\Lib\ModuleHandlers\Offers;

use App\Lib\ModuleHandlers\ModuleHandler;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Offer\SubscriptionSeries;
use App\Models\Offer\SubscriptionSeriesProduct;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SaveSubscriptionSeriesHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class SaveSubscriptionSeriesHandler extends ModuleHandler
{
    /**
     * @var bool $isInitial
     */
    protected bool $isInitial = false;

    /**
     * @var int $offerId
     */
    protected int $offerId = 0;

    /**
     * @var int $orderId
     */
    protected int $orderId = 0;

    /**
     * @var int $orderTypeId
     */
    protected int $orderTypeId = 0;

    /**
     * SaveSubscriptionSeriesHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);

        if ($this->moduleRequest->has('isInitial')) {
            $this->isInitial = $this->moduleRequest->get('isInitial');
        }

        $this->orderId     = $this->moduleRequest->orderId;
        $this->offerId     = $this->moduleRequest->get('offerId', 0);
        $this->orderTypeId = $this->moduleRequest->orderTypeId;
    }

    /**
     * Save the subscription series and it's pieces.
     */
    public function performAction(): void
    {
        // On initials save the subscription series container
        //
        if ($this->isInitial) {
            $this->resource = SubscriptionSeries::create([
                'offer_id'      => $this->offerId,
                'order_id'      => $this->orderId,
                'order_type_id' => $this->orderTypeId,
            ]);
            $this->resourceId = $this->resource->id;
        }
    }

    /**
     * Save a subscription series product entry.
     * @param int $productId
     * @param int $variantId
     * @return Model|null
     */
    public function saveSeriesProduct(int $productId, int $variantId = 0): ?Model
    {
        $seriesProduct = null;

        if ($this->resourceId) {
            $seriesProduct = SubscriptionSeriesProduct::create([
                'subscription_series_id' => $this->resourceId,
                'order_id'               => $this->orderId,
                'order_type_id'          => $this->orderTypeId,
                'product_id'             => $productId,
                'variant_id'             => $variantId,
            ]);
        }

        return $seriesProduct;
    }
}