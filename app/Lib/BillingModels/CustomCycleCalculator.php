<?php


namespace App\Lib\BillingModels;

/**
 * Class CustomCycleCalculator
 * @package App\Lib\BillingModels
 */
class CustomCycleCalculator
{
    /**
     * @var array $productCycleIds
     */
    protected array $productCycleIds = [];

    /**
     * @var array $whiteList
     */
    protected array $whiteList = [];
    /**
     * CustomCycleCalculator constructor.
     * @param array $productCycleIds
     * @param array $whiteList
     */
    public function __construct(array $productCycleIds, $whiteList = [])
    {
        $this->productCycleIds = $productCycleIds;
        $this->whiteList       = $whiteList;
    }

    /**
     * Get the next recurring product at a specific cycle.
     * @param int $cycle
     * @return int|null
     */
    public function nextProductAtCycle(int $cycle): ?int
    {
        $nextProductId = null;

        if (($cycle >= 0) && $this->productCycleIds && is_array($this->productCycleIds)) {
            $count              = count($this->productCycleIds);
            $iterationsComplete = 0;

            while ($this->shouldContinueScanning($nextProductId, $iterationsComplete)) {
                if ($count <= $cycle) {
                    $cycle = $cycle - $count;
                }

                if (isset($this->productCycleIds[$cycle])) {
                    $matchedProduct = $this->productCycleIds[$cycle];

                    if ($this->whiteList) {
                        // if there is a white list then we will skip products not in it
                        //
                        if (in_array($matchedProduct, $this->whiteList)) {
                            $nextProductId = $matchedProduct;
                        } else {
                            // Increment the cycle counter if the matched product is not in the whitelist.
                            //
                            $cycle++;
                        }
                    } else {
                        $nextProductId = $matchedProduct;
                    }

                }

                $iterationsComplete++;
            }
        }

        return $nextProductId;
    }

    /**
     * Determine if the product cycle scan should continue.
     * @param int|null $nextProductId
     * @param int $iterationsComplete
     * @return bool
     */
    private function shouldContinueScanning(?int $nextProductId, int $iterationsComplete): bool
    {
        if ($this->whiteList) {
            $count = count($this->productCycleIds);

            if (!is_null($nextProductId)) {
                $shouldContinueScanning = false;
            } else {
                $shouldContinueScanning = ($count !== $iterationsComplete);
            }
        } else {
            $shouldContinueScanning = is_null($nextProductId);
        }

        return $shouldContinueScanning;
    }
}