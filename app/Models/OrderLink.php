<?php

namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class OrderLink
 * @package App\Models
 */
class OrderLink extends BaseModel
{
    use HasCompositePrimaryKey;

    const CREATED_AT = null;
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    public $table = 'order_link';

    /**
     * @var string
     */
    protected $primaryKey = [
        'master_order_id',
        'linked_order_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'master_order_id',
        'linked_order_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at' => self::CREATED_AT,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(OrderLinkType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
    public function master_order()
    {
        return $this->hasOne(Order::class, 'orders_id', 'master_order_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function linked_order()
    {
        return $this->hasOne(Order::class, 'orders_id', 'linked_order_id');
    }

   /**
    * @param Builder $query
    * @param int     $orderId
    * @return Builder
    */
   public function scopeForAccountUpdaterMaster(Builder $query, $orderId)
   {
       return $query
           ->where('master_order_id', $orderId)
           ->where('type_id', OrderLinkType::ACCOUNT_UPDATER);
   }
}
