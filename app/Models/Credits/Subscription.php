<?php

namespace App\Models\Credits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Credit;

/**
 * Class Subscription
 * @package App\Models
 */
class Subscription extends Credit
{
    const ITEM_TYPE_ID = 1;

    /**
     * @var array
     */
    protected $attributes = [
        'item_type_id' => self::ITEM_TYPE_ID,
    ];

    /**
     * @param Builder $query
     * @param         $value
     * @return Builder
     */
    public function scopeForSubscriptionId(Builder $query, $value)
    {
        return $query->where('item_id', $value);
    }
}
