<?php

namespace App\Traits;

use App\Models\Currency;

trait LineItem
{
    /**
     * @param $amount
     */
    public function add($amount)
    {
        $this->setAttribute('value', $this->getAttribute('value') + $amount);
    }

    /**
     * @param int $id
     */
    public function setCurrencyId(int $id)
    {
        $this->currencyId = $id;

        return $this;
    }

    public function generate()
    {
        $this->setAttribute('sort_order', defined('static::SORT_ORDER') ? static::SORT_ORDER : 999);

        if (defined('static::TITLE')) {
            $this->setAttribute('title', defined('static::TITLE') ? static::TITLE : '');
            $this->setAttribute('text', $this->formatText());
        }
    }

    /**
     * @return string
     */
    protected function formatText()
    {
        return Currency::find($this->currencyId)->format($this->value);
    }
}
