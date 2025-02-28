<?php

namespace App\Models;

use App\Scopes\OrderLineItemScope;
use App\Traits\LineItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class OrderLineItem
 *
 * @package App\Models
 *
 * @method static Builder|self find(string|int $id)
 *
 * @property-read    int    $total      The total amount of the line item(s).
 * @property-read    int    $order_id   The Order ID.
 * @property-read    string $title      The title of the line item.
 * @property-read    string $text       The description of the line item.
 * @property-read    int    $sort_order The priority in which the line item is sorted.
 */
class OrderLineItem extends Model
{
    use LineItem, Eloquence, Mappable;

    protected $table = 'orders_total';
    protected $primaryKey = 'orders_total_id';

    protected $visible = [
        'id',
        'order_id',
        'title',
        'text',
        'value',
        'class',
        'sort_order',
    ];

    protected $appends = [
        'id',
        'order_id',
    ];

    protected $maps = [
        'id'       => 'orders_total_id',
        'order_id' => 'orders_id',
    ];

    protected $fillable = [
        'orders_id',
        'sort_order',
        'value',
        'text',
    ];

    protected $currencyId = 1;

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OrderLineItemScope);

        static::creating(function ($item) {
            if (! $item->getAttribute('class')) {
                if (! defined('static::CLASS_NAME')) {
                    throw new \Exception('Cannot create without class name');
                }

                $item->setAttribute('class', static::CLASS_NAME);
            }

            $item->generate();
        });

        static::updating(function ($item) {
            $item->setAttribute('text', $item->formatText());
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'orders_id');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
