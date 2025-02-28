<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\ReadOnlyModelException;
use App\Traits\ModelImmutable;

/**
 * Base class for "view" models
 * Class ReadOnlyModel
 * @package App\Models
 */
class ReadOnlyModel extends BaseModel
{
    use ModelImmutable;

    /**
     * Throws ReadOnlyModelException on create
     * @param array $attributes
     * @throws ReadOnlyModelException
     */
    public static function create(array $attributes = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on forceCreate
     * @param array $attributes
     * @throws ReadOnlyModelException
     */
    public static function forceCreate(array $attributes)
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on save
     * @param array $options
     * @throws ReadOnlyModelException
     */
    public function save(array $options = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on update
     * @param array $attributes
     * @param array $options
     * @throws ReadOnlyModelException
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on firstOrCreate
     * @param  array  $attributes
     * @param  array  $values
     * @throws ReadOnlyModelException
     */
    public static function firstOrCreate(array $attributes, array $values = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on firstOrNew
     * @param  array  $attributes
     * @param  array  $values
     * @throws ReadOnlyModelException
     */
    public static function firstOrNew(array $attributes, array $values = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on updateOrCreate
     * @param  array  $attributes
     * @param  array  $values
     * @throws ReadOnlyModelException
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on delete
     * @throws ReadOnlyModelException
     */
    public function delete()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on destroy
     * @param mixed $ids
     * @throws ReadOnlyModelException
     */
    public static function destroy($ids)
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on restore
     * @throws ReadOnlyModelException
     */
    public function restore()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on forceDelete
     * @throws ReadOnlyModelException
     */
    public function forceDelete()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on performDeleteOnModel
     * @throws ReadOnlyModelException
     */
    public function performDeleteOnModel()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on push
     * @throws ReadOnlyModelException
     */
    public function push()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on finishSave
     * @param array $options
     * @throws ReadOnlyModelException
     */
    public function finishSave(array $options)
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on performUpdate
     * @param Builder $query
     * @param array $options
     * @throws ReadOnlyModelException
     */
    public function performUpdate(Builder $query, array $options = [])
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on touch
     * @throws ReadOnlyModelException
     */
    public function touch()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on insert
     * @throws ReadOnlyModelException
     */
    public function insert()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }

    /**
     * Throws ReadOnlyModelException on truncate
     * @throws ReadOnlyModelException
     */
    public function truncate()
    {
        throw new ReadOnlyModelException(__METHOD__, get_called_class());
    }
}
