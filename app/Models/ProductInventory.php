<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Sofa\Eloquence\Eloquence;
use App\Lib\Lime\LimeSoftDeletes;
use App\Traits\RevisionableTrait;

/**
 * Class ProductInventory
 * @package App\Models
 * @property int    $id primary key
 * @property int    $product_id product primary key
 * @property int    product_variant_id product variant primary key
 * @property int    $quantity  The total number of items in physical stock
 * @property int    $initial_quantity The total entered number of items
 * @property int    $allocated_quantity The total purchased but not shipped items
 * @property string $name name to be used for this inventory
 * @property string $description description of this inventory
 * @property int    $inventory_threshold quantity level to initiate reorder events
 * @property int    $reorder_amount quantity to reorder
 * @property bool   $reorder_notification turn on reorder notification
 * @property bool   $deleted soft delete flag
 *
 */
class ProductInventory extends Model
{
    use Eloquence, LimeSoftDeletes, RevisionableTrait;

    const ACTIVE_FLAG = false;

    /*
     * int $orderId
     */
    public int $orderId = 0;

    /*
     * int $orderType
     */
    public int $orderType = 0;

    /**
     * @var string
     */
    protected $table = 'product_inventory';

    /**
     * @var bool
     */
    protected $revisionCreationsEnabled = true;

    /**
     * @var string[]
     */
    protected $dontKeepRevisionOf = [
       'created_at',
       'updated_at'
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'product_id',
        'quantity',
        'allocated_quantity',
        'product_name',
        'sku',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'initial_quantity',
        'allocated_quantity',
        'name',
        'description',
        'inventory_threshold',
        'reorder_amount',
        'warehouse_id'
    ];

    /**
     * @var array
     */
    protected $appends = [
        'product_name',
        'sku',
        'category',
        'variant_info',
        'warehouse_info'
    ];

    /**
     * @var array
     */
    protected $maps = [
        'product_name'  => 'product.name',
        'sku'           => 'product.sku',
        'category'      => 'product.category_id',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($inventory) {
            $inventory->created_by = $inventory->created_by ?? get_current_user_id();
        });

        self::updating(function ($inventory) {
            $inventory->updated_by = $inventory->updated_by ?? get_current_user_id();
            $inventory->updated_at = now();
        });
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    /**
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

   /**
    * @return array
    */
    public function getVariantInfoAttribute(): array
    {
        $variant = $this->variant;
        if(isset($variant)){
            return [
                'id'                => $variant->id,
                'sku_num'           => $variant->sku_num,
                'variant_attribute' => $variant->makeHidden(['price', 'quantity', 'weight'])
            ];
        }
        return [];
    }

    /**
     * @return mixed
     */
    public function getProductNameAttribute()
    {
        return $this->product->name;
    }

    /**
     * @return mixed
     */
    public function getSkuAttribute()
    {
        return $this->product->sku;
    }

    /**
     * @return mixed
     */
    public function getCategoryNameAttribute()
    {
        return $this->product->categories->first()->name;
    }

    /**
     * Dynamically appends product attributes to the model to be return through API
     */
    public function appendApiAttributes()
    {
        $visible = [];
        if ($this->product_variant_id) {
            $visible[] = 'variant_info';
        }
        if ($this->warehouse_id) {
            $visible[] = 'warehouse_info';
        }
        $this->setVisible(array_merge($this->visible, $visible));
    }

    /**
     * @return array
     */
    public function getWarehouseInfoAttribute(): array
    {
        if($warehouse = $this->warehouse){
            return [
                'id'   => $warehouse->id,
                'name' => $warehouse->name
            ];
        }
        return [];
    }

    /**
     * @param int $quantity
     * @param int $orderId
     * @param int $orderType
     * @return $this
     */
    public function addAllocatedQuantity(int $quantity, int $orderId, int $orderType = Subscription::TYPE_ORDER): self
    {
        $this->orderId   = $orderId;
        $this->orderType = $orderType;
        $this->update(['allocated_quantity' => $this->allocated_quantity + $quantity]);

        return $this;
    }

    /**
     * @param int $quantity
     * @param int $orderId
     * @param int $orderType
     * @return $this
     */
    public function subtractAllocatedQuantity(int $quantity, int $orderId, int $orderType = Subscription::TYPE_ORDER): self
    {
        $this->orderId     = $orderId;
        $this->orderType   = $orderType;
        // we want to make sure allocated quantity is not going below 0
        //
        $allocatedQuantity = ($this->allocated_quantity - $quantity) <= 0 ? 0 : $this->allocated_quantity - $quantity;
        $this->update(['allocated_quantity' => $allocatedQuantity]);

        return $this;
    }

    /**
     * @param int $quantity
     * @param int $orderId
     * @param int $orderType
     * @return $this
     */
    public function subtractQuantity(int $quantity, int $orderId, int $orderType = Subscription::TYPE_ORDER): self
    {
        $this->orderId   = $orderId;
        $this->orderType = $orderType;
        $this->update(['quantity' => $this->quantity - $quantity]);

        return $this;
    }

    /**
     * @param int  $requestedQuantity
     * @param bool $onlyQuantity
     * @return int
     */
    public function getRemainingQuantity(int $requestedQuantity = 1, bool $onlyQuantity = false): int
    {
        $response = $onlyQuantity ? (int) $this->quantity - $requestedQuantity : $this->available_quantity - $requestedQuantity;
        Log::debug( __METHOD__ . ' Quantity check is ' . $onlyQuantity . ' response ' . $response . ' product id ' . $this->product_id . ' id ' . $this->id);
        return $response;
    }

    /**
     * @param int $productId
     * @param int $variantId
     * @param int $warehouseId
     * @return self|null
     */
    public static function get(int $productId, int $variantId = 0, int $warehouseId = 0): ?self
    {
        return self::where(['product_id' => $productId, 'product_variant_id' => $variantId, 'warehouse_id' => $warehouseId])->first();
    }

    /**
     * @param int $productId
     * @param int $variantId
     * @return Collection|null
     */
    public static function getAll(int $productId, int $variantId = 0): ?Collection
    {
        return self::where(['product_id' => $productId, 'product_variant_id' => $variantId])->get();
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        $this->appendApiAttributes();

        return parent::toArray();
    }

    /**
     * @param int $options
     * @return mixed
     */
    public function toJson($options = 0)
    {
        $this->appendApiAttributes();

        return parent::toJson();
    }

    /**
     * @return int
     */
    public function getAvailableQuantityAttribute(): int
    {
        return (int)$this->quantity - (int)$this->allocated_quantity;
    }
}
