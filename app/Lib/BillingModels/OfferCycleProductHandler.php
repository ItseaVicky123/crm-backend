<?php

namespace App\Lib\BillingModels;

use App\Models\Offer\CycleProduct;
use App\Models\Offer\Offer;
use Illuminate\Support\Collection;
use DateTime;
use fileLogger AS Log;

/**
 * Class OfferCycleProductHandler
 * @package App\Lib\BillingModels
 */
class OfferCycleProductHandler
{
    protected const DATE_FORMAT            = 'm-d';
    protected const DATE_FORMAT_FULL       = 'Y-m-d H:i:s';
    protected const DATE_FORMAT_ZERO_STAMP = 'Y-m-d 00:00:00';

    /**
     * @var Offer $offer
     */
    protected Offer $offer;

    /**
     * @var Collection|null $cycleProductCollection
     */
    protected ?Collection $cycleProductCollection = null;

    /**
     * @var string $currentYear
     */
    private string $currentYear;

    /**
     * OfferCycleProductCollection constructor.
     * @param Offer $offer
     * @throws \Exception
     */
    public function __construct(Offer $offer)
    {
        $this->offer       = $offer;
        $models            = $this->offer->cycle_products()->get();
        $this->currentYear = date('Y');

        if ($this->offer && $models && count($models)) {
            $this->cycleProductCollection = $models;
        } else {
            throw new \Exception("Could not load cycle products from offer #{$this->offer->id}");
        }
    }

    /**
     * Determine the next recurring product ID in a cycle product collection using updated recurring date as input.
     * @param string $dateOverride
     * @return int
     */
    public function findProductByAvailableOn($dateOverride = ''): int
    {
        $nextProductId = 0;
        $current       = new DateTime;
        $stampMap      = [];

        if ($dateOverride) {
            $dateOverrideFormatted = date(self::DATE_FORMAT_ZERO_STAMP, strtotime($dateOverride));
            $current               = $this->getProcessableDateTime(
                DateTime::createFromFormat(self::DATE_FORMAT_FULL, $dateOverrideFormatted)
            );
        }

        $currentTimestamp = $current->getTimestamp();

        /**
         * Parse out the cycle product data
         * @var CycleProduct $data
         */
        foreach ($this->cycleProductCollection as $cycleProduct) {
            $startAtMonth = str_pad($cycleProduct->start_at_month, 2, '0', STR_PAD_LEFT);
            $startAtDay   = str_pad($cycleProduct->start_at_day, 2, '0', STR_PAD_LEFT);

            // Get the available on date from the offer configuration
            //
            if ($startAtMonth && $startAtDay) {
                $startAtDateFull = $this->startAtDateFormatted($startAtMonth, $startAtDay);
                $productId       = $cycleProduct->product_id;
                $targetDate      = DateTime::createFromFormat(self::DATE_FORMAT_FULL, $startAtDateFull);
                $targetStamp     = $targetDate->getTimestamp();

                if (! $this->isProductSkippable($productId)) {
                    // NOTE: This assumes that available on cannot be the same for 2 products!
                    //
                    $stampMap[$targetStamp] = $productId;
                } else {
                    Log::track(__METHOD__, "Skipping available on sync for product #{$productId}", LOG_WARN);
                }
            } else {
                Log::track(__METHOD__, 'Start at month and start at day must both be defined for available on sync', LOG_WARN);
            }
        }

        if ($selectedStamp = $this->findNextAvailableStamp($stampMap, $currentTimestamp)) {
            $nextProductId = $stampMap[$selectedStamp];
        } else {
            Log::track(__METHOD__, 'Unable to find an eligible product ID available', LOG_WARN);
        }

        return $nextProductId;
    }

    /**
     * Determine if product is skippable due to inventory or other conditions
     * NOTE: this is a placeholder for tickets being worked on in parallel.
     * @todo add skippable logic here (inventory, series) - https://sticky.atlassian.net/browse/DEV-1086
     * @param int $productId
     * @return bool
     */
    private function isProductSkippable(int $productId): bool
    {
        return false;
    }

    /**
     * Find the timestamp that lands on or just before the updated next recurring date timestamp.
     * @param array $stampMap
     * @param int $targetTimestamp
     * @return int
     */
    private function findNextAvailableStamp(array $stampMap, int $targetTimestamp): int
    {
        $stamps = array_keys($stampMap);
        sort($stamps, SORT_NUMERIC);
        $lastIndex     = count($stamps) - 1;
        $selectedStamp = 0;

        // Perform stamp comparisons until you get the right one
        //
        foreach ($stamps as $i => $availableOnStamp) {
            // If stamp falls on the same date then take it, the product is available
            //
            if ($availableOnStamp === $targetTimestamp) {
                $selectedStamp = $availableOnStamp;
                break;
            }

            // Otherwise find the first occurrence where the available on stamp is after the target stamp
            //
            if ($availableOnStamp > $targetTimestamp) {
                if ($i === 0) {
                    // It's the 1st item, take the last stamp
                    //
                    $selectedStamp = $stamps[$lastIndex];
                } else {
                    // If it it not the 1st item, we can just take the previous stamp
                    //
                    $selectedStamp = $stamps[$i - 1];
                }

                break;
            } else if ($i == $lastIndex) {
                // Stamp is less than the target stamp and it is the last available on date so use it
                //
                $selectedStamp = $availableOnStamp;
                break;
            }
        }

        return $selectedStamp;
    }

    /**
     * Format the start at month and day.
     * @param string $startAtMonth
     * @param string $startAtDay
     * @return string
     */
    private function startAtDateFormatted(string $startAtMonth, string $startAtDay): string
    {
        return "{$this->currentYear}-{$startAtMonth}-{$startAtDay} 00:00:00";
    }

    /**
     * If the target datetime is in the past, return a datetime of tomorrow to determine the next available product.
     * @param DateTime $target
     * @return DateTime
     */
    private function getProcessableDateTime(DateTime $target): DateTime
    {
        $now = DateTime::createFromFormat(
            self::DATE_FORMAT_FULL,
            date(self::DATE_FORMAT_ZERO_STAMP)
        );
        $processableDateTime = $target;
        $nowTimestamp        = $now->getTimestamp();
        $targetTimestamp     = $target->getTimestamp();

        if ($targetTimestamp < $nowTimestamp) {
            // Get a datetime of tomorrow
            //
            $tomorrowDateString  = date(self::DATE_FORMAT_ZERO_STAMP, strtotime('tomorrow'));
            $processableDateTime = DateTime::createFromFormat(self::DATE_FORMAT_FULL, $tomorrowDateString);
        }

        return $processableDateTime;
    }
}
