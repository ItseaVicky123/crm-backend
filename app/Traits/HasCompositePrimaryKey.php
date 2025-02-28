<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Trait HasCompositePrimaryKey
 * @package App\Traits
 */
trait HasCompositePrimaryKey
{

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        $attributes = [];

        foreach ($this->getKeyName() as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        return $attributes;
    }

    /**
     * @return array|string|null
     */
    public function getKeyName()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        if ($trace[1]['function'] == 'getPrimaryKeyFromParentType') {
            return null;
        }

        return parent::getKeyName();
    }

    /**
     * Set the keys for a save update query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Exception
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        foreach ($this->getKeyName() as $key => $value) {
            // Model already exists and we're now
            // Saving it
            if (is_int($key) && isset($this->$value)) {
                $query->where($value, $this->$value);
            } else if (isset($this->$key)) { // new Model
                $query->where($key, '=', $this->$key);
            } else {
                throw new Exception(__METHOD__ . " Missing part of the primary key: {$key}");
            }
        }

        return $query;
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param array $ids Array of keys, like [column => value].
     * @param array $columns
     * @return mixed|static
     */
    public static function find($ids, $columns = ['*'])
    {
        $me    = new self;
        $query = $me->newQuery();

        foreach ($me->getKeyName() as $key) {
            $query->where($key, '=', $ids[$key]);
        }

        return $query->first($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param mixed $ids
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findOrFail($ids, $columns = ['*'])
    {
        $result = self::find($ids, $columns);

        if (!is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(
            __CLASS__, $ids
        );
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @return $this
     */
    public function refresh()
    {
        if (!$this->exists) {
            return $this;
        }

        $this->setRawAttributes(
            static::findOrFail($this->getKey())->attributes
        );

        $this->load(collect($this->relations)
            ->except('pivot')
            ->keys()
            ->toArray());

        return $this;
    }
}
