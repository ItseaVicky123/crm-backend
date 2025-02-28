<?php

namespace App\Traits;

use App\Models\DeclineManager\ScheduleMeta;

trait HasScheduleBits
{

    /**
     * @var array
     */
    protected static $frequencyData = [];

    /**
     * @var array
     */
    protected static $dayData = [];

    /**
     * @return array
     */
    protected function getScheduleFrequenciesAttribute()
    {
        $values = [];

        if ($this->attributes['schedule_type_id'] == ScheduleMeta::TYPE_DAY) {
            $frequencies = $this->getFrequencyData();


            foreach ($frequencies as $frequency) {
                if ($frequency & $this->attributes['schedule_value']) {
                    $values[] = $frequency;
                }
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    protected function getScheduleDaysAttribute()
    {
        $values = [];

        if ($this->attributes['schedule_type_id'] == ScheduleMeta::TYPE_DAY) {
            $days = $this->getDaysData();

            foreach ($days as $day) {
                if ($day & $this->attributes['schedule_value']) {
                    $values[] = $day;
                }
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    protected function getFrequencyData()
    {
        if (! self::$frequencyData) {
            self::$frequencyData = ScheduleMeta::where('type', 'frequency')
                ->get()
                ->pluck('bit')
                ->toArray();
        }

        return self::$frequencyData;
    }

    /**
     * @return array
     */
    protected function getDaysData()
    {
        if (! self::$dayData) {
            self::$dayData = ScheduleMeta::where('type', 'day')
                ->get()
                ->pluck('bit')
                ->toArray();
        }

        return self::$dayData;
    }
}
