<?php

namespace App\Lib\ModuleHandlers\Offers;

use App\Models\Offer\Offer;
use Illuminate\Support\Facades\DB;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Lib\ModuleHandlers\ModuleRequest;

/**
 * Class AbstractAlreadyPurchasedHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class AbstractAlreadyPurchasedHandler extends ModuleHandler
{
    /**
     * AbstractAlreadyPurchasedHandler constructor.
     * @param ModuleRequest $moduleRequest
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);
    }

    /**
     * Fetch the the unpurchased products and return a whitelist.
     * @param Offer $offer
     * @param string $email
     * @param int $nextRecurringProduct
     * @return array
     */
    protected function fetchEligibleProducts(Offer $offer, string $email, int $nextRecurringProduct = 0): array
    {
        $eligibleProducts = [];

        if ($productCycleIds = $offer->product_cycle_ids) {
            if ($nextRecurringProduct && ! in_array($nextRecurringProduct, $productCycleIds)) {
                $productCycleIds[] = $nextRecurringProduct;
            }

            if ($purchased = $this->fetchPurchasedProducts($email, $productCycleIds)) {
                // There are some purchases, so filter them out of product cycle IDs
                //
                $eligibleProducts = array_diff($productCycleIds, $purchased);
            } else {
                // None were purchased all products are eligible
                //
                $eligibleProducts = $productCycleIds;
            }
        }

        return $eligibleProducts;
    }

    /**
     * Fetch products customers have already purchased.
     * @param string $email
     * @param mixed $productId
     * @return array|null
     */
    protected function fetchPurchasedProducts(string $email, $productId): ?array
    {
        $results  = null;
        $products = is_array($productId) ? $productId : [$productId];

        if ($email && $products) {
            $marks = array_fill(0, count($products), '?');
            $sql   = '
              SELECT sq.product_id
                FROM (
                 SELECT op.orders_id   AS order_id,
                        op.products_id AS product_id,
                        1              AS order_type_id
                   FROM orders_products AS op
                   JOIN orders AS o 
                     ON o.orders_id = op.orders_id
                    AND o.orders_status NOT IN (0,1,7)
                  WHERE o.customers_email_address = ?
                  UNION 
                 SELECT uop.upsell_orders_id AS order_id,
                        uop.products_id      AS product_id,
                        2                    AS order_type_id
                   FROM upsell_orders_products AS uop
                   JOIN upsell_orders AS uo
                     ON uo.upsell_orders_id = uop.upsell_orders_id
                    AND uo.orders_status NOT IN (0,1,7)
                  WHERE uo.customers_email_address = ?
                  UNION
                 SELECT op.orders_id   AS order_id,
                        op.products_id AS product_id,
                        1              AS order_type_id
                   FROM orders_products AS op
                   JOIN orders AS o 
                     ON o.orders_id = op.orders_id
                    AND o.orders_status NOT IN (0,1,7)
                   JOIN order_product_bundle AS opb 
                     ON opb.order_id = o.orders_id 
                    AND opb.product_id = op.products_id 
                    AND opb.is_next_cycle = 0 
                    AND opb.main_flag = 1
                  WHERE o.customers_email_address = ?
                  UNION 
                 SELECT uop.upsell_orders_id AS order_id,
                        uop.products_id      AS product_id,
                        2                    AS order_type_id
                   FROM upsell_orders_products AS uop
                   JOIN upsell_orders AS uo
                     ON uo.upsell_orders_id = uop.upsell_orders_id
                    AND uo.orders_status NOT IN (0,1,7)
                   JOIN order_product_bundle AS uopb 
                     ON uopb.order_id = uo.main_orders_id 
                    AND uopb.product_id = uop.products_id 
                    AND uopb.is_next_cycle = 0 
                    AND uopb.main_flag = 0
                  WHERE uo.customers_email_address = ?
              ) sq
          WHERE product_id IN (' . implode(',', $marks) . ') 
       GROUP BY product_id
            ';
            $binds = array_merge([
                $email,
                $email,
                $email,
                $email
            ], $products);

            if ($data = DB::select(DB::raw($sql), $binds)) {
                $results = [];

                foreach ($data as $row) {
                    $results[] = $row->product_id;
                }
            }
        }

        return $results;
    }

    /**
     * Fetch variants customer has already purchased.
     * @param string $email
     * @param mixed $variantId
     * @return array|null
     */
    protected function fetchPurchasedVariants(string $email, $variantId): ?array
    {
        $results  = null;
        $variants = is_array($variantId) ? $variantId : [$variantId];

        if ($email && $variants) {
            $marks = array_fill(0, count($variants), '?');
            $sql   = '
              SELECT sq.variant_id
                FROM (
                 SELECT op.orders_id  AS order_id,
                        op.variant_id AS variant_id,
                        1             AS order_type_id
                   FROM orders_products AS op
                   JOIN orders AS o 
                     ON o.orders_id = op.orders_id
                    AND o.orders_status NOT IN (0,1,7)
                  WHERE o.customers_email_address = ?
                  UNION 
                 SELECT uop.upsell_orders_id AS order_id,
                        uop.variant_id       AS variant_id,
                        2                    AS order_type_id
                   FROM upsell_orders_products AS uop
                   JOIN upsell_orders AS uo
                     ON uo.upsell_orders_id = uop.upsell_orders_id
                    AND uo.orders_status NOT IN (0,1,7)
                  WHERE uo.customers_email_address = ?
              ) sq
          WHERE variant_id IN (' . implode(',', $marks) . ') 
          LIMIT 1
            ';
            $binds = array_merge([$email, $email], $variants);

            if ($data = DB::select(DB::raw($sql), $binds)) {
                $results = [];

                foreach ($data as $row) {
                    $results[] = $row->variant_id;
                }
            }
        }

        return $results;
    }
}
