<?php

namespace App\Models;

/**
 * Class OrderProductBundle
 * @package App\Models
 */
class OrderProductBundle extends BaseModel
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'order_product_bundle';

    /**
     * @var array
     */
    protected $maps = [
        'is_main' => 'main_flag',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'order_id',
        'bundle_id',
        'product_id',
        'quantity',
        'is_main',
        'is_next_cycle',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_main',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent_product()
    {
        return $this->belongsTo(Product::class, 'bundle_id', 'products_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    /**
     * @return float|int
     * This is the price of a single item within the context of a bundle:
     * There are 3 types of pricing:
     * 1- Fixed: In this case the price of the products within the bundle doesnt matter. The bundle product dictates the price.
     * 2- Per Line Item: In this case the price defined in the bundle product is the price of each of the line items therefore the"
     * price of the whole bundle is the sum of each of the product's price within the bundle multiply by their respective quantity.
     * 3- Based on product price: In this case falls back to the individual product configuration for the products within the bundle.
     */
    public function getProductPriceAttribute()
    {
        $pricingType = $this->parent_product->price_type->id;
        $price       = 0.00;

        switch ($pricingType) {
            case ProductPriceType::PER_ITEM:
            case ProductPriceType::PRODUCT:
                try {
                    $price = ($this->charged_price * 10000)/$this->quantity/10000;
                } catch (\DivisionByZeroError $e) {
                    $price = 0.00;
                }
            break;
        }

        return $price;
    }
}
