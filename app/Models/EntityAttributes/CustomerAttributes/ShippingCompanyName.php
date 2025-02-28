<?php

namespace App\Models\EntityAttributes\CustomerAttributes;

use App\Models\EntityAttribute;

class ShippingCompanyName extends EntityAttribute
{
    const TYPE_ID        = 13; // contact type id
    const ATTRIBUTE_NAME = 'shipping_company_name';
    const DEFAULT_VALUE  = '';

    /**
     * @var array
     */
    protected $attributes = [
        'attr_name' => self::ATTRIBUTE_NAME,
    ];
}
