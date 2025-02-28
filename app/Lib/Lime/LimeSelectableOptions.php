<?php

namespace App\Lib\Lime;

trait LimeSelectableOptions
{
    /**
     * Get the name of the "name" column.
     *
     * @return string
     */
    public function getNameColumn()
    {
        return defined('static::NAME_COLUMN') ? static::NAME_COLUMN : 'name';
    }

    public function toSelectOptions()
    {
       $model = new self;
       $id    = $model->getKeyName();
       $name  = $model->getNameColumn();

       return $model->select($id, $name)
           ->get()
           ->mapWithKeys(
               function($model) use ($id, $name) {
                   return [$model->$id => $model->$name];
               })
           ->toArray();
    }
}
