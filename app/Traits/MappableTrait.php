<?php

namespace App\Traits;

trait MappableTrait
{
    /**
     * Override getAttribute to support mapped attributes.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // ✅ Ensure maps is accessible
        if ($key === 'maps') {
            return $this->maps; // Directly return property instead of using parent method
        }
    
        if (isset($this->maps[$key])) {
            $mappedKey = $this->maps[$key];
    
            // Handle deep mappings like "meta.products_name"
            if (str_contains($mappedKey, '.')) {
                return data_get($this, $mappedKey);
            }
    
            return parent::getAttribute($mappedKey);
        }
    
        return parent::getAttribute($key);
    }

    /**
     * Override setAttribute to support mapped attributes.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (isset($this->maps[$key])) {
            $mappedKey = $this->maps[$key];

            // Handle deep mappings
            if (str_contains($mappedKey, '.')) {
                data_set($this, $mappedKey, $value);
                return $this;
            }

            return parent::setAttribute($mappedKey, $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Ensure mapped attributes appear in array output.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        if (!empty($this->maps) && is_array($this->maps)) {
            foreach ($this->maps as $key => $mappedKey) {
                if (!array_key_exists($key, $attributes) && $this->getAttribute($mappedKey) !== null) {
                    $attributes[$key] = $this->getAttribute($mappedKey);
                }
            }
        }

        return $attributes;
    }
    


    /**
     * Ensure mapped attributes are appended to JSON output.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        return array_merge(parent::getArrayableAppends(), array_keys($this->maps ?? []));
    }

    /**
     * Magic method to dynamically handle calls to getXXXAttribute().
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Handle dynamic accessors (getXXXAttribute)
        if (preg_match('/^get(.+)Attribute$/', $method, $matches)) {
            $attribute = \Illuminate\Support\Str::snake($matches[1]); // Convert CamelCase to snake_case
    
            if (isset($this->maps[$attribute])) {
                return $this->getAttribute($attribute); // Use mapped attribute
            }
        }
    
        return parent::__call($method, $parameters);
    }
}
