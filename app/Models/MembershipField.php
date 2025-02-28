<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class MembershipField
 * @package App\Models
 */
class MembershipField extends BaseModel
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $visible = [
        'name',
        'value',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'name' => 'key',
    ];

    /**
     * @param Builder $query
     * @param         $membershipProviderId
     * @param         $orderId
     * @param         $productId
     * @return Builder
     */
    public function scopeForOrderProduct(Builder $query, $membershipProviderId, $orderId, $productId)
    {
        return $query->where('membership_provider_id', $membershipProviderId)
            ->where('order_id', $orderId)
            ->where('product_id', $productId);
    }
}
