<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Trait Setter
 * @package App\Traits
 */
trait Setter
{
    /**
     * @param $property
     * @param $value
     * @return $this
     */
    public function set($property, $value)
    {
        $hasMaps = property_exists($this, 'maps')
            && is_array($this->maps)
            && (count($this->maps) > 0);
        $camelCase = Str::camel($property);

        if (($function = 'set' . ucfirst($camelCase)) && method_exists($this, $function)) {
            $this->$function($value);
        } elseif ($hasMaps && array_key_exists($property, $this->maps)) {
            $prop          = $this->maps[$property];
            $this->{$prop} = $value;
        } elseif (property_exists($this, $property)) {
            $this->{$property} = $value;
        } elseif (property_exists($this, $camelCase)) {
            $this->{$camelCase} = $value;
        }

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParameters(array $params)
    {
        foreach ($params as $property => $value) {
            $this->set($property, $value);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function setFromProviderProfile()
    {
        if ($this->profile) {
            $fields = $this->profile->fields;

            if ($fields instanceof Collection) {
                foreach ($fields as $field) {
                    $this->set(Str::camel($field->field->api_name), $field->value);
                }
            } elseif (is_array($fields) && array_key_exists('account_fields', $fields)) {
                $this->setParameters($fields['account_fields']);
            }

            $provider = $this->profile->provider ?? $this->profile->account;

            if ($provider) {
                if ($attributes = $provider->provider_attributes) {
                    foreach ($attributes as $attribute) {
                        $this->set(Str::camel($attribute->name), $attribute->value);
                    }
                }
            }
        }

        return $this;
    }
}
