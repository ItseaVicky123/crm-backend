<?php

namespace App\Models\Order;

use App\Models\Order;
use App\Models\Product;
use App\Traits\ModelReader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use ModelReader;

    const UPDATED_AT = null;

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'subscription_id',
    ];

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Using slave connection this function is checking if this Order has at least one Collection Offer Type Product
     *
     * @param $orderId
     * @return bool
     */
    public static function isCollectionOrder($orderId): bool
    {
        $sql = <<<SQL
            SELECT 
                  1
              FROM 
                  `order_items`
              JOIN
                  `subscriptions`
                ON
                  `order_id` = ?
               AND 	  
                  `order_items`.`subscription_id` = `subscriptions`.`id` 
               AND 
                  `offer_type_id` = ? 
               AND 
                  `subscriptions`.`deleted_at` IS NULL 
              LIMIT
                   1;
        SQL;

        return ! empty(\Illuminate\Support\Facades\DB::connection(\App\Models\BaseModel::SLAVE_CONNECTION)->select($sql, [
            $orderId,
            \App\Models\Offer\Type::TYPE_COLLECTION,
        ]));
    }
}
