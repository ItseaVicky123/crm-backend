<?php

namespace App\Traits;

trait HasUuidFind
{
    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        if (is_uuid($id)) {
            return self::where(self::getUuidColumn(), $id)->first($columns);
        }

        return parent::find($id, $columns);
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function findOrFail($id, $columns = ['*'])
    {
        if (is_uuid($id)) {
            return self::where(self::getUuidColumn(), $id)->firstOrFail($columns);
        }

        return parent::findOrFail($id, $columns);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function whereId($id)
    {
        if (is_uuid($id)) {
            return self::where(self::getUuidColumn(), $id);
        }

        return self::where('id', $id);
    }

    /**
     * Get the name of the "uuid" column.
     *
     * @return string
     */
    protected static function getUuidColumn()
    {
        return defined('static::UUID_KEY') ? static::UUID_KEY : 'uuid';
    }
}
