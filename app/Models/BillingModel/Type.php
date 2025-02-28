<?php


namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class Type
 * @package App\Models\BillingModel
 */
class Type extends Model
{

    use Eloquence, Mappable;

    const STRAIGHT_SALE        = 0;
    const BILL_BY_CYCLE        = 1;
    const BILL_BY_DATE         = 2; // Subscription will bill on the Nth day of each month (bill_by_days)
    const BILL_BY_DAY          = 3; // Subscription will bill on the specified day of each month (interval_week, interval_day) (First, Sunday)
    const BILL_BY_SCHEDULE     = 4; // Subscription will bill on the specified calendar day (12th of December)
    const BILL_BY_RELATIVE_DAY = 5; // Subscription will bill on a monthly basis relative to purchase date

    /**
     * @var array
     */
    protected static $week = [
        1 => 'First',
        2 => 'Second',
        3 => 'Third',
        4 => 'Fourth',
        5 => 'Last',
    ];

    /**
     * @var array
     */
    protected static $day = [
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
        7 => 'Saturday',
    ];

    /**
     * @var array
     */
    protected static $month = [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    /**
     * @var string
     */
    public $table = 'vlkp_product_subscription_type';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $maps = [
        'name' => 'value',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billing_model()
    {
        return $this->belongsTo(BillingModel::class, 'bill_by_type_id', 'id');
    }

    /**
     * @param $index
     * @return mixed|string
     */
    public static function getMappedDay($index)
    {
        $day = '';

        if (isset(self::$day[$index])) {
            $day = self::$day[$index];
        }

        return $day;
    }

    /**
     * @param $index
     * @return mixed|string
     */
    public static function getMappedWeek($index)
    {
        $week = '';

        if (isset(self::$week[$index])) {
            $week = self::$week[$index];
        }

        return $week;
    }
}
