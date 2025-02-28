<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Lib\Lime\LimeSoftDeletes;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Models\Product;

/**
 * Class CycleProduct
 * @package App\Models\Offer
 * @property-read Product|null $product
 */
class CycleProduct extends Model
{
    use LimeSoftDeletes, Eloquence, Mappable, HasCreator;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';
    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var string
     */
    public $table = 'billing_product_template';

    /**
     * @var array
     */
    protected $visible = [
        'product_id',
        'name',
        'price',
        'sku',
        'qty',
        'cycle_depth',
        'start_at',
        'start_at_month',
        'start_at_day',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_self_recurring' => 'self_recurring_flag',
        'is_trial'          => 'trial_flag',
        'is_active'         => 'active',
        'is_deleted'        => 'deleted',
        'created_at'        => 'date_in',
        'updated_at'        => 'update_in',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'position',
        'price',
        'sku',
        'qty',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'product_id'  => 'int',
        'cycle_depth' => 'int',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'cycle_depth',
        'start_at_month',
        'start_at_day',
    ];

    /**
     * @return int|null
     */
    protected function getPositionAttribute()
    {
        return $this->cycle_depth + 1;
    }

    /**
     * @return string|null
     */
    protected function getNameAttribute(): ?string
    {
        $this->initProduct();

        return $this->product->name ?? null;
    }
    /**
     * @return string|null
     */
    protected function getPriceAttribute(): ?string
    {
        $this->initProduct();
        $price = (string)($this->product->price ?? '');

        return $price ?? null;
    }
    /**
     * @return string|null
     */
    protected function getSkuAttribute(): ?string
    {
        $this->initProduct();

        return $this->product->sku ?? null;
    }
    /**
     * @return int|null
     */
    protected function getQtyAttribute(): ?int
    {
        $this->initProduct();
        $sku = (int)($this->product->max_quantity ?? 0);

        return $sku ?? null;
    }

    /**
     * Initialize the product relation
     */
    protected function initProduct(): void
    {
        if (!$this->product) {
            $this->product();
        }
    }

    /**
     * @return HasOne
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'products_id', 'product_id');
    }
}
