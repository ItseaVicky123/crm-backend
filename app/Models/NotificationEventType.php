<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use App\Scopes\TypeIdScope;
use App\Models\NotificationEventTypes\Order\Confirmed;
use App\Models\NotificationEventTypes\Order\ProductInformation;
use App\Models\NotificationEventTypes\Order\Shipped;
use App\Models\NotificationEventTypes\Order\Subscription;
use App\Models\NotificationEventTypes\Order\TransactionDeclined;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class NotificationEventType
 * @package App\Models
 */
class NotificationEventType extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    const TARGET_LEVEL_ALL = 1;
    const TARGET_LEVEL_INITIAL = 2;
    const TARGET_LEVEL_SUBSCRIPTION = 4;

    /**
     * @var int
     */
    public $perPage = 100;

    /**
     * @var string
     */
    protected $table = 'notification_event_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'is_product_notification',
        'target_levels',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_deleted'              => 'deleted',
        'is_active'               => 'active',
        'is_product_notification' => 'product_notification',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_product_notification',
        'target_levels',
    ];

    /**
     * @var array
     */
    protected static $targetLevels = [
        [
            'id'   => self::TARGET_LEVEL_ALL,
            'name' => 'All',
        ],
        [
            'id'   => self::TARGET_LEVEL_INITIAL,
            'name' => 'Initial',
        ],
        [
            'id'   => self::TARGET_LEVEL_SUBSCRIPTION,
            'name' => 'Subscription',
        ],
    ];

    /**
     * @var array
     */
    protected static $subscriptionTargetLevels = [
        [
            'id'   => self::TARGET_LEVEL_INITIAL,
            'name' => 'First Rebill',
        ],
        [
            'id'   => self::TARGET_LEVEL_SUBSCRIPTION,
            'name' => 'Subscription',
        ],
    ];

    /**
     * @var array
     */
    protected static $targetedTypes = [
        Shipped::TYPE_ID,
        Confirmed::TYPE_ID,
        TransactionDeclined::TYPE_ID,
        ProductInformation::TYPE_ID,
        Subscription::TYPE_ID,
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new TypeIdScope);
    }

    /**
     * @return array|mixed
     */
    public function getTargetLevelsAttribute()
    {
        $id = $this->getAttribute('id');

        if (in_array($id, self::$targetedTypes)) {
            if ($id == Subscription::TYPE_ID) {
                return self::$subscriptionTargetLevels;
            }

            return self::$targetLevels;
        }

        return [];
    }

    /**
     * @param null $typeId
     * @return array
     */
    public function getAvailableTargetLevels($typeId = null)
    {
        if ($typeId && $typeId == Subscription::TYPE_ID) {
            return collect(self::$subscriptionTargetLevels)
                ->pluck('id')
                ->toArray();
        }

        return collect(self::$targetLevels)
            ->pluck('id')
            ->toArray();
    }

    /**
     * @return array
     */
    public static function getTypesForTargetRequired()
    {
        return self::$targetedTypes;
    }
}
