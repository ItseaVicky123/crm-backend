<?php

namespace App\Lib\BillingModels;

use App\Models\Offer\Offer;
use fileLogger AS Log;

/**
 * Class NextAvailableOnSyncProduct
 * @package App\Lib\BillingModels
 */
class NextAvailableOnSyncProduct
{
    /**
     * @var Offer|null $offer
     */
    private ?Offer $offer = null;

    /**
     * @var string $nextRecurringDate
     */
    private string $nextRecurringDate = '';

    /**
     * @var int
     */
    private int $nextProductId = 0;

    /**
     * NextAvailableOnSyncProduct constructor.
     * @param int $offerId
     * @param string $nextRecurringDate
     * @param int $overrideProductId
     */
    public function __construct(int $offerId, string $nextRecurringDate, int $overrideProductId = 0)
    {
        if (! ($this->nextProductId = $overrideProductId)) {
            try {
                if (!$nextRecurringDate) {
                    throw new \Exception('Cannot determine next available on product without recurring date');
                }

                $this->nextRecurringDate = $nextRecurringDate;
                $this->offer             = Offer::findOrFail($offerId);
                $this->find();
            } catch (\Exception $e) {
                Log::track(__METHOD__, $e->getMessage(), LOG_ERROR);
            }
        }
    }

    /**
     * @return int
     */
    public function getNextProductId(): int
    {
        return $this->nextProductId;
    }

    /**
     * Determine the next available product based on offer available on sync.
     * @todo handle what happens when there is no product when inventory and series offer released
     * https://sticky.atlassian.net/browse/DEV-1086
     * @throws \Exception
     */
    private function find(): void
    {
        $isValidDate = $this->nextRecurringDate && ($this->nextRecurringDate != '0000-00-00');

        if ($isValidDate && $this->offer->shouldSyncAvailableOnDates()) {
            $offerCycleProductHandler = new OfferCycleProductHandler($this->offer);
            $this->nextProductId      = $offerCycleProductHandler->findProductByAvailableOn($this->nextRecurringDate);
        }
    }
}
