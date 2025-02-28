<?php

namespace App\Lib\Lime;

use Illuminate\Support\Facades\DB;

trait LimeSequencer
{
    public static function fetchNextSequence()
    {
        return (new static)->getNextSequence();
    }

    public function getNextSequence()
    {
        $name = $this->getSequenceName();

        if (! $this->checkSequenceValue($name)) {
            throw new \Exception("Unable to fetch sequence for '{$name}'");
        }

        return DB::select(DB::raw("SELECT f_get_sequence('{$name}') AS next"))[0]->next;
    }

    protected function getSequenceName()
    {
        return strtolower(defined('static::SEQUENCE_NAME') ? static::SEQUENCE_NAME : $this->getTable());
    }

    protected function checkSequenceValue()
    {
        $name = $this->getSequenceName();

        $actual = DB::select(DB::raw("                            
         SELECT
               MAX({$this->primaryKey}) AS key_value,
               (
                  SELECT 
                        s.value 
                    FROM 
                        sequence AS s 
                   WHERE 
                        s.name = '{$name}'
               ) AS seq_value
           FROM
               {$name}
         HAVING
               key_value > seq_value
        "));

        if ($actual) {
            return $this->fixSequence($actual[0]->key_value);
        }

        return true;
    }

    protected function fixSequence($value)
    {
        return DB::statement('SELECT f_set_sequence(:name, :val)', ['name' => $this->getSequenceName(), 'val' => $value]);
    }
}
