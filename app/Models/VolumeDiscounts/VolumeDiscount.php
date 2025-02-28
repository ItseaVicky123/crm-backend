<?php

namespace App\Models\VolumeDiscounts;

use App\Models\BaseModel;
use App\Lib\HasCreator;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Product;

/**
 * Class VolumeDiscount
 * @package App\Models\VolumeDiscounts
 */
class VolumeDiscount extends BaseModel
{
    use HasCreator;
    use LimeSoftDeletes;

    const CREATED_BY          = 'created_by';
    const UPDATED_BY          = 'updated_by';
    const DELETED_FLAG        = 'is_deleted';
    const ACTIVE_FLAG         = 'is_active';
    const MAX_QUANTITY_RANGES = 100;
    const VOLUME_DISCOUNTS    = 'volume_discount';
    const APPLY_TO_INITIAL    = 1;
    const APPLY_TO_RECURRING  = 2;
    const APPLY_TO_BOTH       = 3;
    const IS_PRESERVE         = false; //this rule is to not mix preserve price if VD used

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_exclude_non_recurring',
        'apply_to_type_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'is_active',
        'is_exclude_non_recurring',
        'apply_to_type_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'quantities',
        'products',
        'campaigns'
    ];

    /**
     * @var string[] $appends
     */
    protected $appends = [
        'quantities',
        'products',
        'campaigns'
    ];

    /**
     * Boot functions - what to set when an instance is created.
     * Hook into instance actions
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($instance) {
            $instance->created_by = get_current_user_id();
        });
        static::updating(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
        static::deleting(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
    }

    /**
     * Get the quantity configurations owned by this volume discount.
     * @return HasMany
     */
    public function quantities(): HasMany
    {
        return $this->hasMany(VolumeDiscountQuantity::class, 'volume_discount_id');
    }

    /**
     * Get the selected products to apply volume discount.
     * @return HasMany
     */
    public function volume_discount_products(): HasMany
    {
        return $this->hasMany(VolumeDiscountProduct::class, 'volume_discount_id');
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'volume_discount_products',
            'volume_discount_id',
            'product_id',
            'id',
            'products_id'
        );
    }

    /**
     * @return array
     */
    public function getProductsAttribute(): array
    {
        return $this->volume_discount_products->pluck('product_id')->toArray();
    }

    /**
     * Getter for the quantities attribute.
     * @return Collection|null
     */
    public function getQuantitiesAttribute(): ?Collection
    {
        return $this->quantities()->get();
    }

    /**
     * @return BelongsToMany
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'volume_discount_campaigns',
            'volume_discount_id',
            'campaign_id',
            'id',
            'c_id'
        );
    }

    /**
     * Fetch the maximum amount of quantities allowed per volume discount.
     * @return int
     */
    public static function maxQuantitiesAllowed(): int
    {
        return self::MAX_QUANTITY_RANGES;
    }

    /**
     * @param int $numItems
     * @return VolumeDiscountQuantity|null
     */
    public function getQuantityByItemCount(int $numItems): ?VolumeDiscountQuantity
    {
        $volumeDiscountQuantity = null;

        if ($numItems > 0) {
            foreach ($this->quantities as $quantityModel) {
                if ($numItems >= $quantityModel->lower_bound) {
                    if ($quantityModel->upper_bound) {
                        if ($numItems <= $quantityModel->upper_bound) {
                            // If an upper bound was defined and the item count is in range
                            // the order meets criteria for this threshold.
                            //
                            $volumeDiscountQuantity = $quantityModel;
                            break;
                        }
                    } else {
                        // If an upper bound was not defined, the order meets criteria for this threshold.
                        //
                        $volumeDiscountQuantity = $quantityModel;
                        break;
                    }
                }
            }
        }

        return $volumeDiscountQuantity;
    }

    /**
     * @return bool
     */
    public function isPreservePrice(): bool
    {
        return (bool) $this->is_preserve;
    }

    /**
     * @return bool
     */
    public function isExcludeNonRecurring(): bool
    {
        return (bool) $this->is_exclude_non_recurring;
    }

    /**
     * @return bool
     */
    public function applyToInitialOrders(): bool
    {
        return (bool) ($this->apply_to_type_id === self::APPLY_TO_INITIAL || $this->apply_to_type_id === self::APPLY_TO_BOTH);
    }

    /**
     * @return bool
     */
    public function applyToRecurringOrders(): bool
    {
        return (bool) ($this->apply_to_type_id === self::APPLY_TO_RECURRING || $this->apply_to_type_id === self::APPLY_TO_BOTH);
    }

    /**
     * @return HasMany
     */
    public function volume_discount_campaign(): HasMany
    {
        return $this->hasMany(VolumeDiscountCampaign::class, 'volume_discount_id');
    }

    /**
     * @return array
     */
    public function getCampaignsAttribute(): array
    {
        return $this->campaigns()->get()->map(function($campaign) {
            return ['id' => $campaign->c_id, 'name' => $campaign->c_name];
        })->toArray();
    }
}
