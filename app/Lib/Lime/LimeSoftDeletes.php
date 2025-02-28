<?php

namespace App\Lib\Lime;

trait LimeSoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootLimeSoftDeletes()
    {
        static::addGlobalScope(new LimeSoftDeletingScope);
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        $deleted = $this->delete();

        $this->forceDeleting = false;

        return $deleted;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            return $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $updates = [
            $this->getDeletedColumn() => 1,
        ];

        if (($active_column = $this->getActiveColumn()) !== false) {
            $this->{$active_column}  = 0;
            $updates[$active_column] = 0;
        }

        $this->{$this->getDeletedColumn()} = 1;

        $query->update($updates);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedColumn()} = 0;

        if (($active_column = $this->getActiveColumn()) !== false) {
            $this->{$active_column} = 1;
        }

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return $this->{$this->getDeletedColumn()} == 1;
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Get the name of the "deleted" column.
     *
     * @return string
     */
    public function getDeletedColumn()
    {
        return defined('static::DELETED_FLAG') ? static::DELETED_FLAG : 'deleted';
    }

    /**
     * Get the name of the "active" column.
     *
     * @return string
     */
    public function getActiveColumn()
    {
        return defined('static::ACTIVE_FLAG') ? static::ACTIVE_FLAG : 'active';
    }

    /**
     * Get the fully qualified "deleted" column.
     *
     * @return string
     */
    public function getQualifiedDeletedColumn()
    {
        return $this->getTable().'.'.$this->getDeletedColumn();
    }

    /**
     * Get the fully qualified "active" column.
     *
     * @return string
     */
    public function getQualifiedActiveColumn()
    {
        return $this->getActiveColumn() === false
            ? false
            : $this->getTable().'.'.$this->getActiveColumn();
    }
}
