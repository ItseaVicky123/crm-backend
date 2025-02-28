<?php

namespace App\Models\EntityAttributes\CustomerAttributes;

use App\Models\EntityAttribute;

class PersonalIdentificationNumber extends EntityAttribute
{
    const TYPE_ID        = 13; // contact type id
    const ATTRIBUTE_NAME = 'personal_identification_number';
    const DEFAULT_VALUE  = '';

    /**
     * @var array
     */
    protected $attributes = [
        'attr_name' => self::ATTRIBUTE_NAME,
    ];
}
