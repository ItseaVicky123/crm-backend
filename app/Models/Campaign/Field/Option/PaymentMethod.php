<?php

namespace App\Models\Campaign\Field\Option;

use App\Models\Payment\PaymentMethod as PaymentMethodView;

/**
 * Class PaymentMethod
 * @package App\Models\Campaign
 */
class PaymentMethod extends Option
{
    public static function boot()
    {
        parent::boot();

        static::creating(function($method) {
            if ($label = PaymentMethodView::where('name', $method->value)->first()) {
                $method->label = $label->description;
            }
        });
    }
}
