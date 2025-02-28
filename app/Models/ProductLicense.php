<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class ProductLicense
 * @package App\Models
 */
class ProductLicense extends BaseModel
{
    const UPDATED_AT  = null;

    // Statuses
    const STATUS_NEW  = 0;
    const STATUS_HELD = 1;
    const STATUS_ASSIGNED = 2;

    const STATUS_MAP = [
        self::STATUS_NEW      => 'New',
        self::STATUS_HELD     => 'Held',
        self::STATUS_ASSIGNED => 'Assigned',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'held_at',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'key',
        'product_id',
        'status_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'key',
        'product_id',
        'status_id',
        'order_id',
        'created_at',
        'held_at',
        'status_name',
    ];

    protected $searchableColumns = [
        'id',
        'key',
    ];

    protected $appends = [
        'status_name',
    ];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 500;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'orders_id');
    }

    /**
     * @param use Illuminate\Database\Eloquent\Builder $query
     * @param int $product_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct(Builder $query, int $product_id)
    {
        return $query->where('product_id', $product_id);
    }

    /**
     * @param use Illuminate\Database\Eloquent\Builder $query
     * @param int $order_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrder(Builder $query, int $order_id)
    {
        return $query->where('order_id', $order_id);
    }

    /**
     * @param use Illuminate\Database\Eloquent\Builder $query
     * @param int $order_id
     * @param int $product_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrderProduct(Builder $query, int $order_id, int $product_id)
    {
        return $query->where('order_id', $order_id)->where('product_id', $product_id);
    }

    /**
     * @return string
     */
    public function getStatusNameAttribute()
    {
        return self::STATUS_MAP[$this->getAttribute('status_id')];
    }

    public function createOrRestore(array $fill)
    {
        $license = new static;

        try {
            return $license->create($fill);
        } catch (QueryException $e) {
            $deletedLicense = $license->withTrashed()->where('key', $fill['key'])->first();
            if ($deletedLicense && $deletedLicense->deleted_at != null) {
                $deletedLicense->restore();
                if ($deletedLicense->product_id != $fill['product_id']) {
                    $deletedLicense->update(['product_id'=>$fill['product_id']]);
                }

                return $deletedLicense;
            }

            return false;
        }
    }

    /**
     * @param int $orderId
     * @param \App\Models\Product $product
     * @param int $qty
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function grab(int $orderId, Product $product, int $qty = 1): \Illuminate\Support\Collection
    {
        // Put on hold desired quantity of licenses for the order right away
        self::forProduct($product->id)
            ->whereWithComment('License grab and hold')
            ->where('status_id', self::STATUS_NEW)
            ->limit($qty)
            ->update([
                'status_id' => self::STATUS_HELD,
                'held_at'   => Carbon::now(),
                'order_id'  => $orderId,
            ]);

        // Grab all the licenses on hold for this order
        $licenses = self::forOrderProduct($orderId, $product->id)
            ->where('status_id', self::STATUS_HELD)
            ->get();

        $logPrefix = __METHOD__ . " - Order ID: {$orderId}, Product ID: {$product->id}. ";

        // Fail to find any licenses for this product
        if (! $quantity = $licenses->count()) {
            Log::debug($logPrefix . 'No available licenses were found');

            throw new \Exception('Insufficient license keys available');
        }

        $qtyMatched   = $quantity === $qty ? 'Yes' : 'No';
        $licensesList = $licenses->pluck('id')->implode(', ');

        // For feature research, log needed infomation so we can track on onrders that won't get any licenses assigned
        Log::debug($logPrefix . "Licenses IDs: {$licensesList}. Requested qty: {$qty}. Found {$quantity}. Was there enough? {$qtyMatched}");

        return $licenses;
    }

    public function assignToOrder(int $order_id)
    {
        // Find current sequence on the order, if any
        $current_sequence = self::forOrder($order_id)->max('sequence');

        // Assign to order
        $this->changeStatus(self::STATUS_ASSIGNED, [
            'order_id'  => $order_id,
            'sequence'  => (int) $current_sequence + 1,
        ]);

        Log::debug(__METHOD__ . " - Order ID: {$order_id}, Product ID: {$this->product_id}. Assigned License ID: {$this->id}");

        return $this;
    }

    public function hold()
    {
        return $this->changeStatus(self::STATUS_HELD, [
            'held_at' => Carbon::now()
        ]);
    }

    public function release()
    {
        Log::debug(__METHOD__ . " - Order ID: {$this->order_id}, Product ID: {$this->product_id}. Released License ID: {$this->id}");

        return $this->changeStatus(self::STATUS_NEW, [
            'held_at'  => null,
            'order_id' => null,
        ]);
    }

    private function changeStatus(int $status_id, array $additional_changes = [])
    {
        $this->status_id = $status_id;

        foreach ($additional_changes as $prop => $val) {
            $this->setAttribute($prop, $val);
        }

        $this->save();

        return $this;
    }

    public function scopeAssigned()
    {
        return $this->where('status_id', self::STATUS_ASSIGNED);
    }

    public function scopeUnassigned()
    {
        return $this->where('status_id', self::STATUS_NEW);
    }
}
