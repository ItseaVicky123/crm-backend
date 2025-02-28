<?php


namespace App\Lib\Traits;

use Illuminate\Support\Carbon;

/**
 * Class HasFormattableTimestamps
 * @package App\Lib\Traits
 */
trait HasFormattableTimestamps
{
    /**
     * @var string $uiDateFormat
     */
    protected string $uiDateFormat = 'm/d/Y';

    /**
     * @return string
     */
    public function updatedAtUIFormatted(): string
    {
        return $this->getFormattedDateFromCarbon($this->updated_at, $this->uiDateFormat);
    }

    /**
     * @return string
     */
    public function createdAtUIFormatted(): string
    {
        return $this->getFormattedDateFromCarbon($this->created_at, $this->uiDateFormat);
    }

    /**
     * @param mixed $timestamp
     * @param string $format
     * @return string
     */
    protected function getFormattedDateFromCarbon($timestamp, string $format): string
    {
        if ($timestamp instanceof Carbon) {
            return $timestamp->format($format);
        }

        return '';
    }
}