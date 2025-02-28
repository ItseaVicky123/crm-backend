<?php

namespace App\Models;

use App\Events\Order\Captured;
use App\Events\Order\CardUpdated;
use App\Events\Order\Charged;
use App\Events\Order\RecurringDateUpdated;
use App\Events\Order\RecurringDateUpdatedList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Models\Campaign\Postback as CampaignPostback;

/**
 * Class Postback
 * @package App\Models
 */
class Postback extends Model
{
    use Eloquence, HasCreator, SoftDeletes;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    const TYPE_ORDER        = 1;
    const TYPE_PROSPECT     = 2;
    const TYPE_SUBSCRIPTION = 3;
    //Events
    const CHARGED                     = 1;
    const CAPTURED                    = 2;
    const CARD_UPDATED                = 3;
    const RECURRING_DATE_UPDATED      = 4;

    /**
     * @var array
     */
    protected static $httpMethods = [
        'GET',
        'POST',
        'PUT',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'type_id',
        'type',
        'url',
        'is_active',
        'is_global',
        'created_at',
        'updated_at',
        'http_method',
        // Appends
        //
        'type',
        'headers',
        'campaigns',
        'triggers',
        'trigger_type_id',
        'creator',
        'updator',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'type_id',
        'url',
        'is_active',
        'is_global',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'http_method',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type',
        'headers',
        'campaigns',
        'triggers',
        'trigger_type_id',
        'type',
        'creator',
        'updator',
    ];

    /**
     * @var int[]
     */
    public static $events_type_id = [
        self::CHARGED                => Charged::class,
        self::CAPTURED               => Captured::class,
        self::CARD_UPDATED           => CardUpdated::class,
        self::RECURRING_DATE_UPDATED => RecurringDateUpdatedList::class,
    ];

    /**
     * @param Builder $query
     * @param int $typeId
     */
    public function scopeOfType(Builder $query, int $typeId)
    {
        $query->where('type_id', $typeId);
    }

    /**
     * @param Builder $query
     */
    public function scopeGlobal(Builder $query)
    {
        $query->where('is_global', 1);
    }

    /**
     * @param $value
     */
    public function setHttpMethodAttribute($value)
    {
        $value = strtoupper($value);

        if (in_array($value, self::$httpMethods)) {
            $this->attributes['http_method'] = $value;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(PostbackType::class, 'id', 'type_id');
    }

    /**
     * @return mixed
     */
    public function getTypeAttribute()
    {
        return $this->type()->first()->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function headers()
    {
        return $this->hasMany(PostbackHeader::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHeadersAttribute()
    {
        return $this->headers()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(CampaignPostback::class, 'postback_id', 'id');
    }

    /**
     * @return array
     */
    public function getCampaignsAttribute()
    {
        $campaigns = [];

        if (! $this->getAttribute('is_global')) {
            foreach ($this->campaigns()->get() as $campaign) {
                $campaigns[] = $campaign->campaign_id;
            }
        }

        return $campaigns;
    }

    /**
     * @return array
     */
    public static function getHttpMethods()
    {
        return self::$httpMethods;
    }

    /**
     * @return array
     */
    public static function getHttpMethodsForInternalApi()
    {
        $response = [];

        foreach (self::$httpMethods as $method) {
            $response[] = [
                'label' => $method,
                'value' => $method,
            ];
        }

        return $response;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function triggers()
    {
        return $this->hasMany(PostbackTrigger::class, 'postback_id', 'id');
    }

    /**
     * @return array
     */
    public function getTriggersAttribute()
    {
        $triggers = [];

        foreach ($this->triggers()->get() as $trigger) {
            if ($trigger->lookup()->first()->is_multi) {
                $triggers[$trigger->trigger_id][] = $trigger->trigger_option_id;
            } else {
                $triggers[$trigger->trigger_id] = [$trigger->trigger_option_id];
            }
        }

        return $triggers;
    }

    /**
     * @return mixed
     */
    public function getTriggerTypeIdAttribute()
    {
        return $this->triggers()->first()->type_id;
    }
}
