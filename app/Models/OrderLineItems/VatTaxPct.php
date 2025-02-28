<?php

namespace App\Models\OrderLineItems;

use App\Models\OrderLineItem;

class VatTaxPct extends OrderLineItem
{
    const CLASS_NAME = 'ot_vat_tax';

    protected function formatText()
    {
        return $this->value.'%';
    }
}
