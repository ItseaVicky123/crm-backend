<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomModelException;
use App\Exceptions\ResponseCodeException;
use Illuminate\Http\Response;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\Lime\LimeSequencer;
use App\Traits\CustomFieldEntity;
use App\Lib\HasCreator;

/**
 * Class Product
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property float $price
 * @property float $cost_of_goods
 * @property float $restocking_fee
 * @property int $max_quantity
 * @property string $description
 * @property int $taxable
 * @property string $tax_code
 * @property integer $is_trial_product
 * @property integer $collections_flag
 * @property integer $is_licensed
 * @property integer $is_shippable
 * @property string $digital_url
 * @property integer $is_delivery_confirm
 * @property integer $is_bundle
 * @property integer $use_children_sku
 * @property integer $price_type_id see api/app/Models/ProductPriceType.php for the possible values
 * @property integer $max_items
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection $vertical
 * @property-read Category $category
 * @property-read Collection $images
 * @property-read array $custom_fields
 * @property-read Collection $variants
 * @property-read Collection $bundle_children
 * @property-read ProductBundleType $bundle_type
 *
 * @method static Product find(int $id)
 */
class Product extends BaseModel
{
    use LimeSoftDeletes, LimeSequencer, CustomFieldEntity, HasCreator;

    const CREATED_AT = 'products_date_added';
    const UPDATED_AT = 'products_last_modified';
    const UPDATED_BY = 'update_id';
    const CREATED_BY = 'created_id';
    const ENTITY_ID  = 3;

    public $maxPerPage = 100;

    /**
     * @var int
     */
    public $entity_type_id = self::ENTITY_ID;

    /**
     * @var string
     */
    protected $primaryKey = 'products_id';

    /**
     * @var array
     */
    protected $fillable = [
        'sku',
        'price',
        'cost_of_goods',
        'restocking_fee',
        'max_quantity',
        'tax_code',
        'is_taxable',
        'is_shippable',
        'is_delivery_confirm',
        'is_signature_confirm',
        'is_qty_preserved',
        'is_collections_enabled',
        'is_trial_product',
        'weight',
        'weight_unit_id',
        'bundle_type_id',
        'price_type_id',
        'bundle_max_items',
        'has_variants',
        'product_group_attributes',
        'disable_product_swap',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'weight',
        'price',
        'sku',
        'declared_value',
        'cost_of_goods',
        'restocking_fee',
        'is_shippable',
        'category_id',
        'category',
        'inventory',
        'inventories',
        'vertical',
        'legacy_subscription', // @todo deprecate with billing models
        'created_at',
        'updated_at',
        'custom_fields',
        'max_quantity',
        'max_items',
        'is_licensed',
        'is_bundle',
        'is_custom_bundle',
        'is_trial_product',
        'tax_code',
        'taxable',
        'weight_unit',
        'bundle_type',
        'price_type',
        'bundle_children',
        'variants',
        'variantsWithInventory',
        'attributes',
        'images',
        'is_variant_enabled',
        'product_group_attributes',
        'disable_product_swap',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'description',
        'sku',
        'price',
        'weight',
        'declared_value',
        'restocking_fee',
        'cost_of_goods',
        'max_quantity',
        'max_items',
        'created_at',
        'updated_at',
        'custom_fields',
        'vertical',
        'category_id',
        'category',
        'is_bundle',
        'is_custom_bundle',
        'shipping_restrictions',
        'paired_product_ids',
        'default_image',
        'default_email_image',
        'is_variant_enabled',
    ];

    /**
     * @var array
     */
    protected $maps = [
        // IDs
        'id'                     => 'products_id',
        'subscription_type_id'   => 'subscription_type',
        'recur_product_id'       => 'regular_product_id',
        // Flags
        'is_active'              => 'active',
        'is_deleted'             => 'deleted',
        'is_archived'            => 'archived_flag',
        'is_signature_confirm'   => 'signature_confirmation',
        'is_delivery_confirm'    => 'delivery_confirmation',
        'is_taxable'             => 'taxable',
        'is_qty_preserved'       => 'recurring_qty_preserved',
        'is_collections_enabled' => 'collections_flag',
        'is_variant_enabled'     => 'variant_flag',
        // Dates
        'created_at'             => 'products_date_added',
        'updated_at'             => 'products_last_modified',
        'archived_at'            => 'archive_date',
        // Misc
        'max_quantity'           => 'products_quantity',
        'max_items'              => 'bundle_max_items',
        'sku'                    => 'products_sku_num',
        'price'                  => 'products_price',
        'weight'                 => 'products_weight',
        'declared_value'         => 'declaredValue',
        'restocking_fee'         => 'product_restocking_fee',
        'cost_of_goods'          => 'cost_of_goods_sold',
        'subscription_cycle'     => 'days_supply',
        'digital_url'            => 'digital_delivery_URL',
        // Quick relationships
        'name'                   => 'meta.products_name',
        'description'            => 'meta.description',
        'vertical_name'          => 'vertical.name',
        'has_variants'           => 'variant_flag',
        'created_by'             => self::CREATED_BY,
        'updated_by'             => self::UPDATED_BY,
    ];

    /**
     * @var array
     */
    protected $searchableColumns = [
        'meta.products_name',
        'meta.products_description',
        'categories.meta.categories_name',
        'categories.meta.categories_description',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'products_id', // to be extra safes
        'id',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'is_shippable' => 0, // For whatever reason the column default is 1
    ];

    /**
     * @var array
     */
    protected $with = [
        'meta',
    ];

    /**
     * @todo drop
     * products_type
     * products_virtual
     * products_model
     * products_status
     * products_ordered
     * products_quantity_order_min
     * products_quantity_order_units
     * products_priced_by_attribute
     * product_is_free
     * product_is_call
     * products_quantity_mixed
     * product_is_always_free_shipping
     * products_qty_box_status
     * products_quantity_order_max
     * products_sort_order
     * products_discount_type
     * products_discount_type_from
     * products_price_sorter
     * master_categories_id
     * products_mixed_discount_quantity
     * metatags_title_status
     * metatags_products_name_status
     * metatags_products_model_status
     * metatags_products_price_status
     * metatags_title_tagline_status
     * ccbill_subscription_id
     * thanks_mail
     * smtp_host
     * smtp_email
     * smtp_username
     * smtp_password
     * smtp_port
     * confirmation_mail
     * thanks_mail_subject
     * confirmation_mail_subject
     * smtp_fromname
     * thanks_mail_alt_text
     * confirmation_mail_alt_text
     */

    public static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->created_by = get_current_user_id();
        });

        static::updating(function ($product) {
            $product->updated_by = get_current_user_id();
        });

        static::deleting(function ($product) {
            $product->update(['updated_by' => get_current_user_id()]);
            $product->variants()->delete();
            $product->attributes()->delete();
            $product->meta()->delete();
            $product->categories()->detach();
            CustomFieldValue::where('entity_id', $product->id)
                ->where('entity_type_id', self::ENTITY_ID)
                ->delete();
           self::deleteRelatedRecords(intval($product->id));
        });
    }

    // public function getDeclaredValueAttribute()
    // {
    //     return $this->attributes['declaredValue'] ?? null;
    // }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function meta()
    {
        return $this->hasOne(ProductDescription::class, 'products_id', 'products_id');
    }

    /**
     * @return BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'products_to_categories', 'products_id', 'categories_id');
    }

    /**
     * @return HasMany
     */
    public function order_products()
    {
        return $this->hasMany(OrderProduct::class, 'products_id');
    }

    /**
     * @return HasMany
     */
    public function upsell_products()
    {
        return $this->hasMany(UpsellProduct::class, 'products_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vertical()
    {
        return $this->hasOne(Vertical::class, 'id', 'vertical_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function legacy_subscription()
    {
        return $this->hasOne(LegacyProductSubscription::class, 'product_id');
    }

    /**
     * @return array
     */
    public function getPairedProductIdsAttribute()
    {
        return $this->paired_products
            ? $this->paired_products->pluck('id')
            : [];
    }

    /**
     * @return Collection
     */
    public function getVerticalAttribute()
    {
        return $this->vertical()->get();
    }

    /**
     * @return Collection
     */
    public function getCategoriesAttribute()
    {
        return $this->categories()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     */
    public function custom_fields()
    {
        $custom_fields = CustomField::on($this->getConnectionName())
            ->where('entity_type_id', self::ENTITY_ID)
            ->get();

        foreach ($custom_fields as $custom_field) {
            $custom_field->entity_id = $this->getAttribute('id');
            $custom_field->setAppends([
                'type_id',
                'values',
            ])->makeVisible([
                'values',
            ]);
        }

        $real_custom_fields = $custom_fields
            ->filter(function ($field) {
                return (bool) $field->values->count();
            })
            ->values()
            ->all();

        $this->setRelation('custom_fields', $real_custom_fields);

        return $real_custom_fields;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection|mixed
     */
    public function getCustomFieldsAttribute()
    {
        return (array_key_exists('custom_fields', $this->relations))
            ? $this->getRelation('custom_fields')
            : $this->custom_fields();
    }

    /**
     * @return array
     */
    public function getCustomFieldsForLegacyAttribute()
    {
        $legacy = [];

        foreach ($this->getAttribute('custom_fields') as $field) {
            $legacy[] = $field->toArray();
        }

        return $legacy;
    }

    /**
     * @return HasMany
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(ProductLicense::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id', 'products_id')
            ->where('parent_id', 0);
    }

    /*
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAttributesAttribute()
    {
        return $this->attributes()->get();
    }

    /**
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function variantsWithInventory()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->with('inventory', 'inventories');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inventory()
    {
        return $this->hasOne(ProductInventory::class, 'product_id', 'products_id');
    }

   /**
    * @return hasMany
    */
   public function inventories()
   {
      return $this->hasMany(ProductInventory::class, 'product_id', 'products_id');
   }

    /**
     * @return HasMany
     */
    public function shipping_restrictions()
    {
        return $this->hasMany(ProductShippingRestriction::class, 'product_id', 'products_id');
    }

    /**
     * @return array
     */
    public function getShippingRestrictionsAttribute()
    {
        if ($restrictions = $this->shipping_restrictions()->get()) {
            $grouped = $restrictions->groupBy('country_id');
            $values  = [];

            foreach ($grouped as $countryId => $group) {
                $values[] = [
                    'country_id' => $countryId,
                    'values'     => $group->pluck('value'),
                ];
            }

            return $values;
        }

        return [];
    }

    /**
     * @param $countryId
     * @param $location
     * @return bool
     */
    public function canShipTo($countryId, $location)
    {
        return !$this->shipping_restrictions()
            ->where('country_id', $countryId)
            ->where('value', $location)
            ->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne(ProductCategory::class, 'products_id', 'products_id');
    }

    /**
     * @return mixed
     */
    public function getCategoryIdAttribute()
    {
        return $this->category()
            ->first()
            ->category_id;
    }

    /**
     * @return mixed
     */
    public function getCategoryAttribute()
    {
        return $this->categories->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function weight_unit()
    {
        return $this->belongsTo(WeightUnit::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bundle_type()
    {
        return $this->belongsTo(ProductBundleType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function price_type()
    {
        return $this->belongsTo(ProductPriceType::class);
    }

    /**
     * @param array $attributes
     * @throws ResponseCodeException
     */
    public function handleCreateProductAttributes($attributes = [])
    {
        $product_parent_attributes = $this->attributes()->get();
        $attributes_to_delete      = [];

        foreach ($product_parent_attributes as $p_attribute) {
            if (($index = $this->searchAttributeInRequest($p_attribute->name, $attributes)) === false) {
                $attributes_to_delete[] = $p_attribute;
            } else {
                foreach ($p_attribute->options as $c_attribute) {
                    if (!in_array(strtolower($c_attribute->name), array_map('strtolower', $attributes[$index]['options']))) {
                        $attributes_to_delete [] = $c_attribute;
                    } else {
                        unset($attributes[$index]['options'][array_search(strtolower($c_attribute->name), array_map('strtolower', $attributes[$index]['options']))]);
                    }
                }

                if (empty($attributes[$index]['options'])) {
                    unset($attributes[$index]);
                }
            }
        }

        foreach ($attributes_to_delete as $item) {
            $item->delete();
        }

        //Insert the new ones
        foreach ($attributes as $request_parent) {
            $name                     = $request_parent['name'];
            $p_index                  = $this->attributes()->count();
            $c_index                  = 0;
            $parent_product_attribute = $this->attributes()
                ->where('name', $name)
                ->first();

            if ($parent_product_attribute) {
                $c_index = $parent_product_attribute->options
                    ->count();
            } else {
                // Brand new parent
                if ($p_index < 5) {
                    $parent_product_attribute = ProductAttribute::create([
                        'name'       => $name,
                        'product_id' => $this->id,
                        'parent_id'  => 0,
                        'order_key'  => ++$p_index,
                    ]);
                } else {
                    throw new ResponseCodeException('product-attributes.max-values', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            foreach ($request_parent['options'] as $option) {
                $parent_product_attribute->attributes()->create([
                    'name'       => $option,
                    'product_id' => $this->id,
                    'parent_id'  => $parent_product_attribute->id,
                    'order_key'  => ++$c_index,
                ]);
            }
        }
    }

    public function handleAutoCreateProductVariants()
    {
        $p_count              = $this->attributes()->count();
        $p_in_variant         = false;
        $attributes_to_insert = [];

        foreach ($this->attributes()->get() as $p_attribute) {
            $attributes_to_insert = [];
            $p_in_variant         = false;
            $variant_count        = $this->variants()->count();

            foreach ($p_attribute->options as $c_attribute) {
                if (!$c_attribute->variants()->count()) {
                    $attributes_to_insert[] = $c_attribute;
                } else {
                    $p_in_variant = true;
                }
            }

            foreach ($attributes_to_insert as $c_attribute) {
                if (!$p_in_variant) {
                    if (!$variant_count) {
                        ProductVariant::create([
                            'product_id' => $this->id,
                            'sku_num'    => "{$this->sku}-1",
                            'order_key'  => 1,
                        ]);
                    }

                    $this->variants()->each(function ($variant) use ($c_attribute) {
                        ProductVariantAttribute::create([
                            'variant_id'   => $variant->id,
                            'attribute_id' => $c_attribute->id,
                        ]);
                    });

                    $p_in_variant = true;
                } else {
                    $variants = $p_attribute->attributes()
                        ->whereHas('variants')
                        ->first()
                        ->variants()
                        ->get();
                    $v_count  = $this->variants()->count();

                    foreach ($variants as $variant) {
                        $new_variant = ProductVariant::create([
                            'product_id' => $this->id,
                            'sku_num'    => "{$this->sku}-" . (++$v_count),
                            'order_key'  => $v_count,
                        ]);

                        foreach ($variant->variant()->first()->attributes()->get() as $variant_attribute) {
                            if ($variant_attribute->attribute()->first()->parent->id != $p_attribute->id) {
                                ProductVariantAttribute::create([
                                    'variant_id'   => $new_variant->id,
                                    'attribute_id' => $variant_attribute->attribute()
                                        ->first()
                                        ->id,
                                ]);
                            }
                        }

                        ProductVariantAttribute::create([
                            'variant_id'   => $new_variant->id,
                            'attribute_id' => $c_attribute->id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param array $attributes
     * @throws \Exception
     */
    public function handleDeleteProductAttributes($attributes = [])
    {
        foreach ($attributes as $attribute) {
            $attribute_id             = $attribute['id'];
            $parent_product_attribute = $this->attributes()
                ->where('id', $attribute_id)
                ->first();

            if ($parent_product_attribute) {
                if (count($attribute['options']) == 1 && $attribute['options'][0] == 'ALL') {
                    $parent_product_attribute->delete();
                } else {
                    foreach ($attribute['options'] as $option) {
                        if ($child = $parent_product_attribute->attributes()
                            ->where('id', $option)
                            ->first()) {
                            $child->delete();
                        }
                    }
                }
            }
        }

        if (!$this->attributes()->count()) {
            $this->update([
                'has_variants' => 0,
            ]);
        }
    }

    /**
     * @param $attribute_name
     * @param $attributes
     * @return bool|int|string
     */
    protected function searchAttributeInRequest($attribute_name, $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if (strtolower($attribute['name']) == strtolower($attribute_name)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getIsBundleAttribute()
    {
        return $this->attributes['bundle_type_id'] ? 1 : 0;
    }

    /**
     * @return int
     */
    public function getIsCustomBundleAttribute()
    {
        return $this->attributes['bundle_type_id'] == ProductBundleType::getCustomType() ? 1 : 0;
    }

    /**
     * If this is a bundle product that is a Custom Bundle OR Prebuilt with use_children_sku ON,
     * then this bundle's children are inventory eligible. Othewise this bundle would be considered as a regular product
     *
     * @return bool
     */
    public function isBundleUsesChildrenSkus(): bool
    {
        return $this->is_bundle && ($this->is_custom_bundle || $this->use_children_sku);
    }

    /**
     * @return int
     */
    public function getIsPrebuiltBundleAttribute()
    {
        return (int) ($this->bundle_type_id == ProductBundleType::getPrebuiltType());
    }

    /**
     * @return HasMany
     */
    public function bundle_children()
    {
        return $this->hasMany(ProductBundleChild::class, 'bundle_product_id', 'products_id');
    }

    /**
     * @return Collection
     */
    public function getBundleChildrenAttribute()
    {
        return $this->bundle_children()->get();
    }

    /**
     * @return Collection
     */
    public function getChildrenAttribute()
    {
        return $this->getBundleChildrenAttribute();
    }

    /**
     * @return BelongsToMany
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class, 'image_product', 'product_id', 'image_id')
            ->withPivot('is_default', 'is_default_email')
            ->where('entity_type_id', self::ENTITY_ID);
    }

    /**
     * @return Image|null
     */
    public function getDefaultImageAttribute()
    {
        return $this->images()
            ->wherePivot('is_default', 1)
            ->first();
    }

    /**
     * @return Image|null
     */
    public function getDefaultEmailImageAttribute()
    {
        return $this->images()
                ->wherePivot('is_default_email', 1)
                ->first() ?? $this->default_image;
    }

    /**
     * @return BelongsToMany
     */
    function paired_products()
    {
        return $this->belongsToMany(Product::class, 'product_pair_once', 'product_id', 'paired_id');
    }

    /**
     * @return BelongsToMany
     */
    function paired_to()
    {
        return $this->belongsToMany(Product::class, 'product_pair_once', 'paired_id', 'product_id');
    }

   /**
    * Get the maximum product quantity, taking into account variants and override option
    * @param int $variantId
    * @param null|int $overrideQuantity
    * @return int
    */
    public function getMaxQuantityCheckVariant($variantId = 0, $overrideQuantity = null): int
    {
       $quantity = 1;
       $maxQty   = GetMaxProductQuantity($this->id, $variantId);

       if (! is_null($overrideQuantity)) {
          $quantity = (int) $overrideQuantity;
       }

       // Fail safe--Can't have a quantity of zero
       //
       $quantity = max($quantity, 1);
       $maxQty   = (! ($maxQty > 0) ? 1 : $maxQty);

       // As long as the quantity exceeds the set max, it is ok to assume at least
       // the max as the quantity--anything more is breaking the rules.
       //
       if ($quantity > $maxQty) {
          $quantity = $maxQty;
       }

       return $quantity;
    }

    /**
     * Inject some validation into my parent's delete method
     *
     * @return bool|null
     * @throws \App\Exceptions\CustomModelException
     */
    public function delete(): ?bool
    {
        // Am I associated with any orders or upsells?
        if ($this->hasOrdersOrUpsells()) {
            throw new CustomModelException('products.delete-product-associated');
        }

        // Am I recurring on any other products? (Legacy object model)
        if ($this->isLegacyRecurringOnAnotherProduct()) {
            throw new CustomModelException('products.delete-product-recurring');
        }

        // Am I assigned directly to a campaign? (Legacy object model)
        if ($this->isAttachedToLegacyCampaign()) {
            throw new CustomModelException('products.delete-product-campaign');
        }

        return parent::delete();
    }

    /**
     * Legacy/NUTRA object-model helper.
     * Am I recurring on a product other than myself?
     *
     * The Legacy/NUTRA data model used a 'product chain'
     * model which had products recurring on other products.
     *
     * @return bool
     */
    public function isLegacyRecurringOnAnotherProduct(): bool
    {
        $query = 'SELECT 1 FROM products WHERE `regular_product_id` = ? AND `products_id` != ? LIMIT 1';
        return count(DB::select($query, [
            $this->products_id,
            $this->products_id
        ]));
    }

    /**
     * Am I attached to any orders or upsells?
     *
     * @return bool
     */
    public function hasOrdersOrUpsells(): bool
    {
        $query   = "
            SELECT 1 FROM `orders_products`        WHERE `products_id` = ?
            UNION
            SELECT 1 FROM `upsell_orders_products` WHERE `products_id` = ? LIMIT 1;
        ";

        $rows = DB::select($query, [
            $this->products_id,
            $this->products_id
        ]);

        return count($rows);
    }

    /**
     * Am I attached directly to a campaign? (Legacy object model)
     *
     * @return bool
     */
    public function isAttachedToLegacyCampaign(): bool
    {
        $query = 'SELECT 1 FROM `campaign_products` WHERE product_id = ? LIMIT 1';
        return count(DB::select($query, [
            $this->products_id
        ]));
    }


    /**
     * Calculate the bundle subtotal.
     * @param int $quantity
     * @param array $children
     * @return float|null
     * @throws \Exception
     */
    public function calculatedBundleSubtotal(int $quantity = 1, array $children = []): ?float
    {
        $subtotal = null;

        if ($this->is_bundle) {
            $unitPrice   = null;
            $priceTypeId = $this->price_type_id;

            if ($priceTypeId == ProductPriceType::FIXED) {
                // If the bundle product price type is fixed then the entire bundle
                // is the same price regardless of the children within.
                //
                $unitPrice = $this->price;
            } else {
                // Fetch the order product bundles to calculate the price
                // for per item and product price types.
                // NOTE: Variant children not currently supported.
                // NOTE: Multiple line items with the same bundle is not supported.
                //
                $childProductPrices = [];

                if ($this->is_prebuilt_bundle) {
                    foreach ($this->bundle_children as $bundleChild) {
                        $childProductPrices[] = $this->calculateBundleChildSubtotal($bundleChild->product, $bundleChild->quantity);
                    }
                } else if ($this->is_custom_bundle && $children) {
                    foreach ($children as $child) {
                        if (isset($child['product_id'], $child['quantity'])) {
                            $childProduct         = Product::findOrFail($child['product_id']);
                            $childProductPrices[] = $this->calculateBundleChildSubtotal($childProduct, $child['quantity']);
                        }
                    }
                }

                $unitPrice = array_sum($childProductPrices);
            }

            $subtotal = $unitPrice * $quantity;
        }

        return $subtotal;
    }

    /**
     * Calculate the number of items in a product or bundle.
     * @param int $quantity
     * @param array $children
     * @return int
     */
    public function calculatedItemCount(int $quantity, array $children = []): int
    {
        if ($this->is_bundle) {
            $childItemCount = 0;

            if ($this->is_custom_bundle && $children) {
                foreach ($children as $child) {
                    if (isset($child['quantity'])) {
                        $childItemCount += $child['quantity'];
                    }
                }
            } else if ($this->is_prebuilt_bundle) {
                foreach ($this->bundle_children as $bundleChild) {
                    $childItemCount += $bundleChild->quantity;
                }
            }

            $itemCount = ($quantity * $childItemCount);
        } else {
            $itemCount = $quantity;
        }

        return $itemCount;
    }

    /**
     * @param Product $child
     * @param int $quantity
     * @return float
     * @throws \Exception
     */
    private function calculateBundleChildSubtotal(Product $child, int $quantity = 1): float
    {
        switch ($this->price_type_id) {
            case ProductPriceType::PRODUCT:
                return $child->price * $quantity;
            case ProductPriceType::PER_ITEM:
                return $this->price * $quantity;
            default:
                throw new \Exception('Unsupported price type ID');
        }
    }

    /**
     * @param $product_id int
     * @return void
     */
    public static function deleteRelatedRecords(int $product_id): void
    {
        // also remove that product from offers, campaign, etc.
        \App\Models\Offer\Product::where('product_id', $product_id)->delete();
        \App\Models\Campaign\Product::where('product_id', $product_id)->delete();
    }

    public function productEvent(): HasMany
    {
        return $this->hasMany(ProductEvent::class, 'product_id');
    }

    /**
     * Variants without set price, quantity or weight will get those values from the product and null otherwise
     * this function changes the null for the product value
     *
     */
    public function getVariantsWithValues(): Collection
    {
        $variants = $this->variants()->with('images')->get();

        $variants->each(function ($variant) {
            if ($variant->price === null) {
                $variant->price = $this->price;
            }
            if ($variant->quantity === null) {
                $variant->quantity = $this->max_quantity;
            }
            if ($variant->weight === null) {
                $variant->weight = $this->weight;
            }

            $variant->makeVisible('images');
        });

        return $variants;
    }

}
