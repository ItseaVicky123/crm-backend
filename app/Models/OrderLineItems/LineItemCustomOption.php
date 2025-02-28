<?php


namespace App\Models\OrderLineItems;

use App\Models\BaseModel;
use App\Lib\HasCreator;
use App\Lib\Traits\HasOrderByType;
use App\Models\ConfigSetting;
use App\Models\Subscription;

/**
 * Class LineItemCustomOption
 * @package App\Models\OrderLineItems
 */
class LineItemCustomOption extends BaseModel
{
    use HasCreator;
    use HasOrderByType;

    const CREATED_BY          = 'created_by';
    const UPDATED_BY          = 'updated_by';
    const MAX_OPTIONS_CEILING = 50;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'order_id',
        'order_type_id',
        'subscription_id',
        'name',
        'value',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'value',
        'order_id',
        'order_type_id',
        'subscription_id',
        'subscription_status',
        'id'
    ];

    /**
     * @var array
     */
    protected $appends = [
        'subscription_status',
    ];

    /**
     * Boot functions - what to set when an instance is created.
     * Hook into instance actions
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($instance) {
            $instance->created_by = get_current_user_id();
        });
        static::updating(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
        static::deleting(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
    }

    /**
     * Get the options as array results by subscription ID.
     * @param string $subscriptionId
     * @return array
     */
    public static function arrayResultBySubscriptionId(string $subscriptionId): array
    {
        $data        = [];
        $collections = self::where('subscription_id', $subscriptionId)->get();

        if ($collections && $collections->isNotEmpty()) {
            foreach ($collections as $collection) {
                $data[] = [
                    'id'    => $collection->id,
                    'name'  => $collection->name,
                    'value' => $collection->value,
                ];
            }
        }

        return $data;
    }

    /**
     * The maximum amount of options per line item.
     * @return int
     */
    public static function maxOptionsPerLineItem(): int
    {
        $max = 1;

        if ($config = ConfigSetting::key('LINE_ITEM_OPTION_MAX_COUNT')->first()) {
            $max = min((int) $config->value, self::MAX_OPTIONS_CEILING);
        }

        return $max;
    }

    /**
     * @return mixed
     */
    public function subscription()
    {
        return (new Subscription())->getSubscriptionById($this->subscription_id);
    }

    /**
     * @return mixed
     */
    public function getSubscriptionStatusAttribute()
    {
        return $this->subscription()->legacy_status;
    }
}
