<?php

namespace App\Lib\Payments\Nmi\Classes;

use Illuminate\Support\Arr;
use App\Lib\Payments\Nmi\Interfaces\MerchantFieldsProvider;

class MerchantFields implements MerchantFieldsProvider
{
    /**
     * Prefix added onto the keys when items set in the $merchantDefinedFields array
     * Once set on an object, it should not be
     * @var string
     */
    public const FIELD_PREFIX = 'merchant_defined_field_';

    /**
     * @var array
     */
    protected array $merchantDefinedFields = [];

    /**
     * @param int $index
     * @param $value
     * @return MerchantFieldsProvider
     */
    public function setField(int $index, $value): MerchantFieldsProvider
    {
        Arr::set($this->merchantDefinedFields, self::FIELD_PREFIX.$index, $value);

        return $this;
    }

    /**
     * @param int $index
     * @return MerchantFieldsProvider
     */
    public function removeField(int $index): MerchantFieldsProvider
    {
        Arr::forget($this->merchantDefinedFields, $index);

        return $this;
    }

    /**
     * @param int $index
     * @return mixed
     */
    public function getField(int $index)
    {
        return Arr::get($this->merchantDefinedFields, self::FIELD_PREFIX."_$index");
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->merchantDefinedFields;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->merchantDefinedFields;
    }

    /**
     * Set the current object's properties from an array;
     *
     * @param array $data
     * @return MerchantFieldsProvider
     */
    public function fromArray(array $data = []): MerchantFieldsProvider
    {
        foreach ($data as $key => $value) {
            $this->setField($key, $value);
        }

        return $this;
    }

}
