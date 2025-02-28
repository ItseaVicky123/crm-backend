<?php

namespace App\Lib\VolumeDiscounts\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\VolumeDiscounts\VolumeDiscount;
use Illuminate\Validation\ValidationException;

/**
 * Class SaveRequest
 * @package App\Lib\VolumeDiscounts\ModuleRequests
 */
class SaveRequest extends ModuleRequest
{
    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = true;

    /**
     * SaveRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $requiredOnly             = $this->isCreate ? 'required|' : '';
        $maxQuantitiesPerDiscount = VolumeDiscount::maxQuantitiesAllowed();
        $rules                    = [
            'name'                          => "{$requiredOnly}max:255",
            'description'                   => "max:1000",
            'is_active'                     => 'bool',
            'is_preserve'                   => 'bool',
            'quantities'                    => "{$requiredOnly}array|between:1,{$maxQuantitiesPerDiscount}",
            'quantities.*.lower_bound'      => "{$requiredOnly}int|min:1|max:999999",
            'quantities.*.upper_bound'      => 'int|max:999999',
            'quantities.*.discount_type_id' => "{$requiredOnly}int|in:1,2",
            'quantities.*.amount'           => "{$requiredOnly}numeric|min:0.01",
        ];
        $attributes = [
            'name'                          => 'Volume Discount Name',
            'description'                   => 'Volume Discount Description',
            'is_active'                     => 'Active Flag',
            'is_preserve'                   => 'Preserve Price Flag',
            'quantities'                    => 'Volume Discount Quantities',
            'quantities.*.lower_bound'      => 'Lower Bound Quantity',
            'quantities.*.upper_bound'      => 'Upper Bound Quantity',
            'quantities.*.discount_type_id' => 'Discount Type',
            'quantities.*.amount'           => 'Discount Amount',
        ];


        if (!$this->isCreate) {
            $rules['id']                   = 'required|int|exists:mysql_slave.volume_discounts,id';
            $rules['quantities.*.id']      = 'int|exists:mysql_slave.volume_discount_quantities,id';
            $rules['is_replace']           = 'bool';

            // On update require discount fields without id
            //
            $updateOnlyRequirement = '|required_without:id';
            $rules['quantities.*.lower_bound']      .= $updateOnlyRequirement;
            $rules['quantities.*.discount_type_id'] .= $updateOnlyRequirement;
            $rules['quantities.*.amount']           .= $updateOnlyRequirement;

            $attributes['id']              = 'Volume Discount ID';
            $attributes['quantities.*.id'] = 'Volume Discount Quantity ID';
            $attributes['is_replace']      = 'Replace Quantity Flag';
        }

        $this->validate($rules, $attributes);
    }
}
