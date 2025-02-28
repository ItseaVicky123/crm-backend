<?php

namespace App\Models\OrderAttributes;

use App\Models\OrderAttribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class IsPaysafeTrial
 * @package App\Models\OrderAttributes
 */
class IsPaysafeTrial extends OrderAttribute
{
    const TYPE_ID = 52;
    const IS_IMMUTABLE = true;
    const DEFAULT_VALUE = 1;
    const IGNORE_DUPLICATES = true;

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public function scopeRequiredForOrder(Builder $query, $order_id)
    {
        return $query
            ->where('order_id', $order_id);
    }
}
