<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface MerchantFieldsProvider extends Arrayable
{
    /**
     * @param int $index
     * @param $value
     * @return self
     */
    public function setField(int $index, $value): self;

    /**
     * @param int $index
     * @return self
     */
    public function removeField(int $index): self;

    /**
     * @param int $index
     * @return mixed
     */
    public function getField(int $index);

    /**
     * @return array
     */
    public function getFields(): array;
}