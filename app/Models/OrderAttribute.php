<?php

namespace App\Models;

use App\Models\OrderAttributes\ConsentWorkflowType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Lib\Lime\LimeSoftDeletes;
use App\Scopes\TypeIdScope;
use App\Exceptions\OrderAttributeImmutableException;

/**
 * Class OrderAttribute
 *
 * @package App\Models
 *
 * @method static Builder forOrder(string $order_id);
 * @method static Builder forOrderWhereValue(string $order_id, string $value);
 */
class OrderAttribute extends BaseModel
{
    use LimeSoftDeletes;

    const IS_IMMUTABLE = false;
    const DEFAULT_VALUE = null;
    const IGNORE_DUPLICATES = false;

    /**
     * @var array
     */
    protected $table = 'order_attribute';

    /**
     * @var int
     */
    protected $order_id;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var int
     */
    protected $type_id;

    /**
     * @var int
     */
    protected $active;

    /**
     * @var int
     */
    protected $deleted;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $attributes = [
        'active'  => 1,
        'deleted' => 0,
    ];

    /**
     * @var array
     */
    protected $visible = [
        'order_id',
        'type_id',
        'value',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'value',
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new TypeIdScope);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'orders_id');
    }

    /**
     * @param Builder $query
     * @param int     $order_id
     * @return Builder
     */
    public function scopeForOrder(Builder $query, int $order_id)
    {
        return $query->where('order_id', $order_id);
    }

    /**
     * @param Builder $query
     * @param string $order_id
     * @param string $value
     * @return Builder
     */
    public function scopeForOrderWhereValue(Builder $query, string $order_id, string $value): Builder
    {
        return $query->where('order_id', $order_id)
            ->where('value', $value);
    }

    /**
     * @param      $order
     * @param      $value
     * @param bool $ignoreDuplicate
     * @return mixed
     * @throws OrderAttributeImmutableException
     */
    public static function createForOrder($order, $value = null, $ignoreDuplicate = false)
    {
        $order_id = $order instanceof Order ? $order->id : $order;
        $value    = $value ?? static::DEFAULT_VALUE;

        if (is_null($value)) {
            Log::debug('No value set for ' . __METHOD__);

            return null;
        }

        $existing = static::where('order_id', $order_id);

        if ($attr = $existing->first()) {
            if ($ignoreDuplicate || static::IGNORE_DUPLICATES) {
                return $attr;
            }

            if (static::IS_IMMUTABLE) {
                throw new OrderAttributeImmutableException(get_called_class() . ' is immutable');
            }

            if ($attr instanceof ConsentWorkflowType) {
                $attr->setAttribute('value', $value);
            }

            $attr->update(['value' => $value]);

            return $attr;
        } else {
            return self::create([
                'order_id' => $order_id,
                'value'    => $value,
            ]);
        }
    }

    /**
     * @param Model|null $value
     * @return bool
     */
    public function is($value)
    {
        return $this->getAttribute('value') == $value;
    }

    public function __toString()
    {
        return $this->getAttribute('value');
    }
}
