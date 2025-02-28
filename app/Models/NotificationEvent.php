<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class NotificationEvent
 * @package App\Models\
 */
class NotificationEvent extends BaseModel
{

    use LimeSoftDeletes, HasCreator;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';

    /**
     * Hardcoded internal created ID used for system created notifications
     */
    public const SYSTEM_CREATED_ID = 1;

    /**
     * @var string
     */
    protected $table = 'notification_event';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'template_id',
        'smtp_id',
        'sms_id',
        'event_type_id',
        'target_level',
        'is_active',
        'updated_at',
        'created_at',
        'created_by',
        'updated_by',
        'bcc_emails',
        'product_count',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'template_id',
        'smtp_id',
        'sms_id',
        'event_type_id',
        'target_level',
        'is_active',
        'updated_id',
        'created_id',
        'bcc_emails',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'  => 'active',
        'is_deleted' => 'deleted',
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
        'created_by' => 'creator.name',
        'updated_by' => 'updator.name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'event_type_id',
        'is_active',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'bcc_emails',
        'product_count',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in',
    ];

    /**
     * @var bool|string
     */
    private static $activeColumn = false;

    public static function boot()
    {
        parent::boot();

        static::deleting(function ()
        {
            self::$activeColumn = 'active';
        });
    }

    /**
     * @param $value
     */
    public function setEventTypeIdAttribute($value)
    {
        $this->attributes['type_id'] = $value;
    }

    /**
     * @return mixed
     */
    public function getEventTypeIdAttribute()
    {
        return $this->attributes['type_id'];
    }

    /**
     * @param array $value
     */
    public function setBccEmailsAttribute(array $value)
    {
        $this->attributes['bcc_emails'] = implode(',', $value);
    }

    /**
     * @return array
     */
    public function getBccEmailsAttribute()
    {
        return ($this->attributes['bcc_emails'] ? explode(',', $this->attributes['bcc_emails']) : []);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(ProductEvent::class, 'event_id', 'id');
    }

    /**
     * @return int
     */
    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function template()
    {
        return $this->hasOne(NotificationTemplate::class, 'id', 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sms_provider()
    {
        return $this->hasOne(NotificationProviderProfile::class, 'id', 'sms_id')
            ->forType(NotificationProviderAccount::SMS_TYPE);
    }

    /**
     * The active column only determines if the
     * Event is enabled or disabled. To use LimeSoftDeletes
     * This method is needed so that inactive Events are
     * not considered deleted. However, in the boot() method,
     * this value is set to `active` when deleting an instance
     * @return bool|string
     */
    public function getActiveColumn()
    {
        return self::$activeColumn;
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationEventType::class, 'type_id', 'id');
    }
}
