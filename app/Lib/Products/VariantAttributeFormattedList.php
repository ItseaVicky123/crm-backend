<?php


namespace App\Lib\Products;

use App\Models\Product;
use Illuminate\Support\Collection;
use product\variant;

/**
 * Class VariantAttributeFormattedList
 * @package App\Lib\Products
 */
class VariantAttributeFormattedList extends Collection
{
    /**
     * @var Product $product
     */
    private Product $product;

    /**
     * VariantAttributeFormattedList constructor.
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        parent::__construct([]);

        $this->product = $product;

        // Populate the collection with the variant attribute list.
        //
        if ($variantCollection = $this->product->variants) {
            $productId = $this->product->id;
            $pattern   = '/NAME_(\d+)/';

            foreach ($variantCollection as $variant) {
                $variantId          = $variant->id;
                $displayPrice       = is_null($variant->price) ? $this->product->price : $variant->price;
                $variantPrice       = number_format($displayPrice, 2);
                $variantObject      = new variant(['product_id' => $productId]);
                $attributeData      = $variantObject->fetch_attribute_data([$variantId]);
                $attributesFiltered = [];
                $attributesText     = '';

                foreach ($attributeData as $key => $map) {
                    if (preg_match($pattern, $key)) {
                        $attributesFiltered[] = $map[0];
                    }
                }

                if ($attributesFiltered) {
                    $attributesText = implode(' / ', $attributesFiltered) . " - {$variantPrice}";
                }

                $this->put($variantId, [
                    'variantId' => $variantId,
                    'all'       => $attributesFiltered,
                    'text'      => $attributesText
                ]);
            }
        }
    }
}
