<?php

namespace App\Lib\ModuleHandlers\Offers;

use Illuminate\Support\Facades\DB;
use App\Exceptions\ModuleHandlers\ModuleHandlerException;
use App\Lib\ModuleHandlers\ModuleRequest;

/**
 * Class AlreadyPurchasedProductHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class AlreadyPurchasedProductHandler extends AbstractAlreadyPurchasedHandler
{
    /**
     * @var string|null $email
     */
    private string $email;

    /**
     * @var int $productId
     */
    private int $productId;

    /**
     * @var int $variantId
     */
    private int $variantId;

    /**
     * InitialSeriesProductHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);

        $this->email     = $this->moduleRequest->get('email', '');
        $this->productId = $this->moduleRequest->get('productId', 0);
        $this->variantId = $this->moduleRequest->get('variantId', 0);
    }

    /**
     * Throw an exception if product or variant has already been purchased.
     * @throws ModuleHandlerException
     */
    public function performAction(): void
    {
        if ($this->hasPurchasedVariant()) {
            throw (
                new ModuleHandlerException(
                    __METHOD__,
                    'offers.series-already-purchased-variant',
                    [
                        '{id}'    => $this->variantId,
                        '{email}' => $this->email,
                    ]
                )
            )->translateDataToMessage();
        } else if ($this->hasPurchasedProduct()) {
            throw (
                new ModuleHandlerException(
                    __METHOD__,
                    'offers.series-already-purchased-product',
                    [
                        '{id}'    => $this->productId,
                        '{email}' => $this->email,
                    ]
                )
            )->translateDataToMessage();
        }
    }

    /**
     * Determine if customer has purchased product.
     * @return bool
     */
    protected function hasPurchasedProduct(): bool
    {
        $hasPurchased = false;

        if ($this->email && $this->productId) {
            $hasPurchased = (bool) $this->fetchPurchasedProducts($this->email, $this->productId);
        }

        return $hasPurchased;
    }

    /**
     * Determine if customer has purchased variant.
     * @return bool
     */
    protected function hasPurchasedVariant(): bool
    {
        $hasPurchased = false;

        if ($this->email && $this->variantId) {
            $hasPurchased = (bool) $this->fetchPurchasedVariants($this->email, $this->variantId);
        }
        return $hasPurchased;
    }
}