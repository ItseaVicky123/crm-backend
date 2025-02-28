<?php

namespace App\Models\EntityAttributes\CustomerAttributes;

use App\Models\EntityAttribute;

class BillingCompanyName extends EntityAttribute
{
    const TYPE_ID        = 13; //contact type id
    const ATTRIBUTE_NAME = 'billing_company_name';
    const DEFAULT_VALUE  = '';

    /**
     * @var array
     */
    protected $attributes = [
        'attr_name' => self::ATTRIBUTE_NAME,
    ];
}
