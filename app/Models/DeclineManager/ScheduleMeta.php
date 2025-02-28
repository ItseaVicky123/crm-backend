<?php

namespace App\Models\DeclineManager;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class ScheduleMeta
 * @package App\Models\DeclineManager
 */
class ScheduleMeta extends Model
{

    use Eloquence;

    const TYPE_DAILY   = 1;
    const TYPE_WEEKLY  = 2;
    const TYPE_MONTHLY = 3;
    const TYPE_CYCLE   = 4;
    const TYPE_DATE    = 5;
    const TYPE_DAY     = 6;

    /**
     * @var string
     */
    public $table = 'vlkp_decline_salvage_schedule';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'bit',
    ];

    /**
     * @var array
     */
    protected static $scheduleTypes = [
        self::TYPE_DAILY   => 'Daily',
        self::TYPE_WEEKLY  => 'Weekly',
        self::TYPE_MONTHLY => 'Monthly',
        self::TYPE_CYCLE   => 'Custom Cycle',
        self::TYPE_DATE    => 'Custom Date',
        self::TYPE_DAY     => 'Custom Day',
    ];

    public static function boot()
    {
        static::addGlobalScope(new ActiveScope());
    }

    /**
     * @return array
     */
    public static function getScheduleTypes()
    {
        return self::$scheduleTypes;
    }

    /**
     * @return array
     */
    public static function getCustomScheduleTypes()
    {
        return [
            self::TYPE_CYCLE => 'Custom Cycle',
            self::TYPE_DATE  => 'Custom Date',
            self::TYPE_DAY   => 'Custom Day',
        ];
    }
}
