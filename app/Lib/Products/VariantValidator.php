<?php


namespace App\Lib\Products;

/**
 * Class VariantValidator
 * @package App\Lib\Products
 */
class VariantValidator
{
    /**
     * Verify that there are no variant attribute data nodes with duplicate attribute names.
     * @param array $variantData
     * @throws \Exception
     */
    public function scanForDuplicateAttributes(array $variantData)
    {
        $existingAttributeNames = [];

        if (count($variantData) && ! isset($variantData['variant_id'])) {
            foreach ($variantData as $attributeData) {
                if (isset($attributeData['attribute_name'])) {
                    $name = trim($attributeData['attribute_name']);

                    if (in_array($name, $existingAttributeNames)) {
                        throw new \Exception("Attribute name {$name} is duplicated in the request node", 400);
                    }

                    $existingAttributeNames[] = $name;
                }
            }
        }
    }
}