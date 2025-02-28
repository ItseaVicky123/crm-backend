<?php

namespace App\Models\Offer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Models\BillingModel\BillingModel;

/**
 * Class Configuration
 * @package App\Models\Offer
 */
class Configuration extends Model
{
    use SoftDeletes, Eloquence, HasCreator;

    const CREATED_BY = 'created_by';

    const UPDATED_BY = 'updated_by';

    /**
     * @var string
     */
    public $table = 'offer_configurations';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'group_id',
        'name',
        'billing_model',
        'product',
        'default_quantity',
        'price',
        'trial_product',
        'is_upsell',
        'step_number',
        'prepaid_cycles',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'children',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'group_id',
        'name',
        'offer_id',
        'billing_model_id',
        'product_id',
        'default_quantity',
        'price',
        'trial_product_id',
        'is_upsell',
        'step_number',
        'prepaid_cycles',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'billing_model',
        'product',
        'trial_product',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'children',
        'is_grouped',
        'is_main',
    ];

    /**
     * @var array
     */
    protected $searchableColumns = [
        'id',
        'group_id',
        'name',
        'billing_model.name',
        'step_number',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function($offerConfig) {
            if ($parent = $offerConfig->parent) {
                if (! $parent->is_main) {
                    $parent->update([
                        'group_id' => $parent->id,
                    ]);
                }
            }
        });

        static::deleted(function($offerConfiguration) {
            $offerConfiguration->update([
                'updated_by' => \current_user(),
            ]);

            if ($offerConfiguration->children()->exists()) {
                $offerConfiguration->children()->update([
                    'updated_by' => \current_user(),
                    'deleted_at' => Carbon::now(),
                ]);
            }

            if ($parent = $offerConfiguration->parent) {
                // no moar children
                //
                if (! $parent->children()->exists()) {
                    $parent->update([
                        'group_id'   => 0,
                        'updated_by' => \current_user(),
                    ]);
                }
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model()
    {
        return $this->hasOne(BillingModel::class, 'id', 'billing_model_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getBillingModelAttribute()
    {
        return $this->billing_model()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    protected function getProductAttribute()
    {
        if ($product = $this->product()->first()) {
            $product = $product->only([
                'id',
                'name',
            ]);
        }

        return $product;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function trial_product()
    {
        return $this->hasOne(Product::class, 'product_id', 'trial_product_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getTrialProductAttribute()
    {
        if ($product = $this->trial_product()->first()) {
            $product = $product->only([
                'id',
                'name',
            ]);
        }

        return $product;
    }

    /**
     * @return Carbon
     */
    protected function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at']);
    }

    /**
     * @return Carbon
     */
    protected function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(static::class, 'group_id', 'id')
            ->where('id', '!=', $this->getAttribute('id'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildrenAttribute()
    {
        $children = $this->children()->get();
        $children->makeHidden([
            'children',
            'name',
        ]);

        return $children;
    }

    /**
     * @return bool
     */
    public function getIsGroupedAttribute()
    {
        return $this->group_id > 0;
    }

    /**
     * @return bool
     */
    public function getIsMainAttribute()
    {
        return $this->id == $this->group_id;
    }

    /**
     * @return Configuration|null
     */
    public function getParentAttribute()
    {
        if ($this->is_grouped && ! $this->is_main) {
            return self::where('id', $this->group_id)->first();
        }

        return null;
    }
}
