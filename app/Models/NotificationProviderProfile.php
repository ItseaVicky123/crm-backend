<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class NotificationProviderProfile
 * @package App\Models
 */
class NotificationProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    const PROVIDER_TYPE = 21;

    /**
     * @var string
     */
    protected $table = 'notification_providers';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'account_id',
        'alias',
        'generic_id',
        'account_type',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
        'account_type',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'account_name' => 'account.name',
        'account_type' => 'account.notification_type',
    ];

    /**
     * @var array
     */
    protected static $hours = [
        0  => '12 AM Eastern',
        1  => '1 AM Eastern',
        2  => '2 AM Eastern',
        3  => '3 AM Eastern',
        4  => '4 AM Eastern',
        5  => '5 AM Eastern',
        6  => '6 AM Eastern',
        7  => '7 AM Eastern',
        8  => '8 AM Eastern',
        9  => '9 AM Eastern',
        10 => '10 AM Eastern',
        11 => '11 AM Eastern',
        12 => '12 PM Eastern',
        13 => '1 PM Eastern',
        14 => '2 PM Eastern',
        15 => '3 PM Eastern',
        16 => '4 PM Eastern',
        17 => '5 PM Eastern',
        18 => '6 PM Eastern',
        19 => '7 PM Eastern',
        20 => '8 PM Eastern',
        21 => '9 PM Eastern',
        22 => '10 PM Eastern',
        23 => '11 PM Eastern',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile)
        {
            $profile->setGenericId();
        });

        static::deleting(function ($profile)
        {
            $profile->fields()->delete();
            $profile->provider_custom_fields()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(NotificationProviderAccount::class, 'account_id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param                                       $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForType(Builder $query, $type)
    {
        return $query->with([
            'account' => function ($nestedQuery) use ($type) {
                $nestedQuery->with([
                    'provider_attributes' => function ($deeplyNested) use ($type) {
                        $deeplyNested->where('attribute_name', 'notification_type_id')
                            ->where('attribute_value', $type);
                    },
                ]);
            },
        ]);
    }

    /**
     * @return array|string[]
     */
    public static function getHours()
    {
        return self::$hours;
    }
}
