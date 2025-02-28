<?php

namespace App\Lib\Lime;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LimeSoftDeletingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['ForceDelete', 'Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedColumn(), 0);

        if ($model->getActiveColumn() !== false) {
            $builder->where($model->getQualifiedActiveColumn(), 1);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $updates = [
                $this->getDeletedColumn($builder) => 1
            ];

            if (($active_column = $this->getActiveColumn($builder)) !== false) {
                $updates[$active_column] = 0;
            }

            return $builder->update($updates);
        });
    }

    /**
     * Get the "deleted" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getDeletedColumn(Builder $builder)
    {
        if (count($builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedColumn();
        } else {
            return $builder->getModel()->getDeletedColumn();
        }
    }

    /**
     * Get the "deleted" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getActiveColumn(Builder $builder)
    {
        if (count($builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedActiveColumn();
        } else {
            return $builder->getModel()->getActiveColumn();
        }
    }

    /**
     * Add the force delete extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addForceDelete(Builder $builder)
    {
        $builder->macro('forceDelete', function (Builder $builder) {
            return $builder->getQuery()->delete();
        });
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            $updates = [
                $builder->getModel()->getDeletedColumn() => 0,
            ];

            if (($active_col = $builder->getModel()->getActiveColumn()) !== false) {
                $updates[$active_col] = 1;
            }

            return $builder->update($updates);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where($model->getQualifiedDeletedColumn(), 0);

            if (($active_col = $model->getQualifiedActiveColumn()) !== false) {
                $builder->where($active_col, 1);
            }

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedDeletedColumn(), 1)
                ->where($model->getQualifiedActiveColumn(), 0);

            return $builder;
        });
    }
}
