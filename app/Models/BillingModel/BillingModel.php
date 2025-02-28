<?php

namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use App\Lib\Lime\LimeSoftDeletes;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Traits\HasImmutable;

/**
 * Class BillingModel
 * @package App\Models\BillingModel
 */
class BillingModel extends Model
{
    use LimeSoftDeletes, Mappable, Eloquence, HasCreator, HasImmutable;

    const CREATED_BY   = 'created_by';
    const UPDATED_BY   = 'updated_by';
    const CREATED_AT   = 'date_in';
    const UPDATED_AT   = 'update_in';
    const IS_IMMUTABLE = 'locked_flag';

    const BILL_BY_CYCLE        = 1; // Subscription will bill every N days (bill_by_days)
    const BILL_BY_DATE         = 2; // Subscription will bill on the Nth day of each month (bill_by_days)
    const BILL_BY_DAY          = 3; // Subscription will bill on the specified day of each month (interval_week, interval_day) (First, Sunday)
    const BILL_BY_SCHEDULE     = 4; // Subscription will bill on the specified calendar day (12th of December)
    const BILL_BY_RELATIVE_DAY = 5; // Subscription will bill every X day of the month or year

    /**
     * @var string
     */
    protected $table = 'billing_frequency';

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in',
    ];

    /**
     * @var array
     */
    protected $maps = [
        // Flags
        //
        'is_preserve_quantity' => 'preserve_quantity',
        'is_locked'            => 'locked_flag',
        'is_immutable'         => 'locked_flag',
        'is_deleted'           => 'deleted',
        'is_active'            => 'active',
        'is_archived'          => 'archived',
        'is_default'           => 'default_flag',
        'creator_id'           => 'created_by',
        'updator_id'           => 'updated_by',
        // Relationship IDs
        //
        'type_id'              => 'bill_by_type_id',
        'week'                 => 'interval_week',
        'day'                  => 'interval_day',
        'days'                 => 'bill_by_days',
        'date'                 => 'bill_by_days',
        'frequency_type_id'    => 'interval_week',
        'frequency'            => 'interval_day',
        // Dates
        //
        'created_at'           => self::CREATED_AT,
        'updated_at'           => self::UPDATED_AT,
        // Other
        //
        'cut_off_day'          => 'buffer_days',
        'trial_setting'        => 'based_off_of_end_of_trial',
    ];

    /**
     * DO NOT CHANGE
     * Unable to use $guarded because of maps
     * @var string[]
     */
    protected $fillable = [
        'name',
        'frequency',
        'frequency_type_id',
        'type_id',
        'creator_id',
        'updator_id',
        'buffer_days',
        'expire_cycles',
        'week',
        'day',
        'days',
        'date',
        'cut_off_day',
        'is_immutable',
        'is_locked',
        'is_active',
        'is_deleted',
        'is_default',
        'is_archived',
        'is_preserve_quantity',
        'trial_setting',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'buffer_days',
        'expire_cycles',
        'frequency',
        'frequency_type_id',
        'is_default',
        'is_archived',
        'is_preserve_quantity',
        'created_at',
        'updated_at',
        'description',
        'week',
        'day',
        'days',
        'date',
        'dates',
        'type',
        'cut_off_day',
        'creator',
        'updator',
        'creator_id',
        'updator_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
        'updated_at',
        'is_archived',
        'is_preserve_quantity',
        'is_default',
        'week',
        'day',
        'days',
        'date',
        'dates',
        'description',
        'type',
        'frequency',
        'frequency_type_id',
        'cut_off_day',
        'creator_id',
        'updator_id',
        'trial_setting',
    ];


   /**
    * @var array|string[]
    */
   protected static array $allTypesVisible = [
      'id',
      'name',
      'description',
      'frequency',
      'frequency_type_id',
      'is_default',
      'is_archived',
      'is_preserve_quantity',
      'created_at',
      'updated_at',
      'type',
      'creator',
      'updator',
   ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($billingModel) {
            $billingModel->created_by = get_current_user_id();
        });

        static::updating(function ($billingModel) {
            $billingModel->checkImmutable();
            $billingModel->updated_by = get_current_user_id();
        });

        static::deleting(function ($billingModel) {
            $billingModel->checkImmutable();
            $billingModel->updated_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function templates()
    {
        return $this->belongsToMany(
            Template::class,
            'billing_subscription_frequency',
            'frequency_id',
            'template_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'bill_by_type_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getTypeAttribute()
    {
        return $this->type()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function week()
    {
        return $this->hasOne(Week::class, 'id', 'interval_week');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getWeekAttribute()
    {
        return $this->week()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function day()
    {
        return $this->hasOne(Day::class, 'id', 'interval_day');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDayAttribute()
    {
        return $this->day()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dates()
    {
        return $this->hasMany(Date::class, 'frequency_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDatesAttribute()
    {
        return $this->dates()->get();
    }

    /**
     * @return array
     */
    public function getSortedFrequencyDatesAttribute(): array
    {
        $dates = [];

        if ($collection = $this->getDatesAttribute()) {
            if ($collection->isNotEmpty()) {
                foreach ($collection as $item) {
                    $dates[$item->billing_month][$item->billing_day] = true;
                }
            }
        }

        return $dates;
    }

    /**
     * @return string
     */
    public function getDescriptionAttribute()
    {
        switch ($this->attributes['bill_by_type_id']) {
            case Type::BILL_BY_CYCLE:
                return "Bills every {$this->attributes['bill_by_days']} days";
            case Type::BILL_BY_DATE:
                return sprintf('Bills on the %s day of the month', ordinal($this->attributes['bill_by_days']));
            case Type::BILL_BY_DAY:
                $template = 'Bills on the %s %s of the month';
                $token    = Type::getMappedWeek($this->attributes['interval_week']);

                if ($this->attributes['bill_by_days']) {
                    $template = 'Bills every %s %s';
                    $token    = ordinal($this->attributes['bill_by_days']);
                }

                return sprintf(
                    $template,
                    $token,
                    Type::getMappedDay($this->attributes['interval_day'])
                );
            case Type::BILL_BY_SCHEDULE:
                return 'Bills on the scheduled month and day';
            case Type::BILL_BY_RELATIVE_DAY:
                return 'Bills on an interval relative to purchase day';
            case Type::STRAIGHT_SALE:
                return 'One time purchase';
            default:
                return '';
        }
    }

    /**
     * Adding some manipulation here
     * Because we have some maps and other
     * stuffs that only apply depending on the type
     * @return array
     */
    public function toArray()
    {
        switch ($this->attributes['bill_by_type_id']) {
            case Type::BILL_BY_CYCLE:
                $this->visible = array_merge(self::$allTypesVisible, ['days']);
            break;
            case Type::BILL_BY_DATE:
                $this->visible = array_merge(self::$allTypesVisible, ['date']);
            break;
            case Type::BILL_BY_DAY:
                $this->visible = array_merge(self::$allTypesVisible, [
                    'days',
                    'week',
                    'day',
                    'cut_off_day',
                ]);
            break;
            case Type::BILL_BY_SCHEDULE:
                $this->visible = array_merge(self::$allTypesVisible, [
                    'dates',
                    'buffer_days',
                ]);
            break;
            case Type::BILL_BY_RELATIVE_DAY:
                $this->visible = array_merge(self::$allTypesVisible, [
                    'frequency',
                    'frequency_type_id',
                    'trial_setting',
                ]);
            break;
            case Type::STRAIGHT_SALE:
            default:
                $this->visible = self::$allTypesVisible;
        }

        return parent::toArray();
    }
}
