<?php

namespace App\Models\Offer;

use App\Exceptions\CustomModelException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Lib\HasCreator;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Campaign\Campaign;
use App\Models\BillingModel\OrderSubscription;
use App\Models\BillingModel\Template;
use App\Events\Offer\Created as OfferCreatedEvent;
use App\Traits\HasImmutable;
use App\Models\TrialWorkflow\TrialWorkflow;
use App\Models\BillingModel\BillingModel;
use App\Models\Offer\BillingModel AS OfferBillingModel;
use App\Lib\Traits\HasFormattableTimestamps;
use App\Models\Offer\Type as OfferType;

/**
 * Class Offer
 * @package App\Models\Offer
 * @property-read \App\Models\Product|null $terminating_cycle_product
 * @property-read OfferLink|null $linkToChild
 * @property-read Collection|null $linksToParents
 * @property-read Offer|null $child
 * @property-read Collection|null $parens
 */
class Offer extends Model
{
    use LimeSoftDeletes;
    use Eloquence;
    use Mappable;
    use HasCreator;
    use HasImmutable;
    use HasFormattableTimestamps;

    const UPDATED_AT   = 'update_in';
    const CREATED_AT   = 'date_in';
    const DELETED_FLAG = 'archived'; // pseudo
    const CREATED_BY   = 'created_by';
    const UPDATED_BY   = 'updated_by';

    const TERMINATE_HOLD       = 1; // On Last cycle, put subscription on hold
    const TERMINATE_SELF_RECUR = 2; // On Last cycle, self recur forever
    const TERMINATE_PRODUCT    = 3; // On last cycle, recur to a product and then put it on hold
    const TERMINATE_RESTART    = 4; // On last cycle, restart the subscription
    const TERMINATE_COMPLETE   = 5; // On last cycle, the subscription is complete

    /**
     * @var string
     */
    public $table = 'billing_offer';

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in',
    ];

    /**
     * @var array
     */
    protected $hidden = [
        'date_in',
        'update_in',
        'archived',
        'trial_flag',
        'trial_price_flag',
        'delayed_billing_flag',
        'delayed_billing_price_flag',
        'trial_inherit_frequency_flag',
        'created_by',
        'updated_by',
        'terminating_cycle_type_id',
        'trial_days',
        'trial_price',
        'delayed_billing_days',
        'delayed_billing_price',
        'is_delayed_email_suppressed',
        'is_trial_custom_price',
        'is_delayed_billing',
        'is_delayed_billing_custom_price',
        'is_trial_duration_inherited',
        'template_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'billing_models'                  => 'offer_billing_models',
        'is_archived'                     => 'archived',
        'is_trial'                        => 'trial_flag',
        'is_trial_custom_price'           => 'trial_price_flag',
        'is_delayed_billing'              => 'delayed_billing_flag',
        'is_delayed_billing_custom_price' => 'delayed_billing_price_flag',
        'is_trial_duration_inherited'     => 'trial_inherit_frequency_flag',
        'trial_workflows'                 => 'trialWorkflows',
        'created_at'                      => 'date_in',
        'updated_at'                      => 'update_in',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
        'updated_at',
        'is_archived',
        'products',
        'billing_models',
        'is_prepaid',
        'is_series',
        'type',
        'cycle_type',
        'terminating_cycle_type',
        'prepaid_profile',
        'trial',
        'trial_workflows',
        'cycle_products',
        'seasonal_products',
        'is_trial',
        'terminating_product_name',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'expire_cycles',
        'cycle_type_id',
        'terminating_cycle_type_id',
        'terminating_product_id',
        'template_id',
        'delayed_billing_days',
        'delayed_billing_price',
        'trial_price',
        'trial_days',
        'is_trial',
        'is_trial_custom_price',
        'is_delayed_billing',
        'is_delayed_billing_custom_price',
        'is_delayed_email_suppressed',
        'is_trial_duration_inherited',
        'type_id',
        'is_seasonal',
        'is_archived',
        'created_by',
        'updated_by',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($offer) {
            if (! $offer->created_by) {
                $offer->created_by = get_current_user_id();
            }

            if (! $offer->template_id) {
                $offer->template_id = Template::create([
                    'name' => $offer->name,
                ])->id;
            }

            Event::dispatch(new OfferCreatedEvent($offer));
        });

        static::updating(function ($offer) {
            $offer->checkImmutable();
            $offer->updated_by = get_current_user_id();
        });

        static::deleting(function ($offer) {
            $offer->checkImmutable();
            // Remove all the relationships
            //
            $offer->updated_by = get_current_user_id();

            $offer->products()->delete();
            $offer->offer_configurations()->delete();
            $offer->supplemental_products()->delete();

            if ($offer->is_prepaid) {
                /**
                 * This is done using the attribute
                 * Rather than the relationship
                 * Because PrepaidProfile has
                 * It's own relationships it needs
                 * To delete
                 */
                $offer->prepaid_profile->delete();
            }

            if ($offer->isCollectionType()) {
                $offer->offer_details()->delete();
            }

            $offer->linksToParents()->delete();
            $offer->linkToChild()->delete();

            /**
             * Same thing here as for PrepaidProfile...
             * Difference here is that there
             * is a hasMany relationship for BillingModel
             * So need to loop through each so it can delete
             * it's relationships too
             */
            $offer->billing_models
                ->each(function ($billingModel) {
                    $billingModel->delete();
                });
            $offer->billing_template()->delete();
            $offer->save();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'billing_campaign_offer', 'c_id', 'campaign_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'offer_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Collection|null
     */
    protected function getProductsAttribute()
    {
        return $this->products()->get();
    }

    /**
     * @return HasOne
     */
    public function billing_template()
    {
        return $this->hasOne(Template::class, 'id', 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function order_subscriptions()
    {
        return $this->hasMany(OrderSubscription::class);
    }

    /**
     * @return HasOne
     */
    public function prepaid_profile()
    {
        return $this->hasOne(PrepaidProfile::class, 'offer_id', 'id');
    }

    /**
     * Check if the type id is explicitely prepaid.
     * @return bool
     */
    public function typeIsPrepaid(): bool
    {
        return $this->type_id === OfferType::TYPE_PREPAID;
    }

    /**
     * @return int
     */
    protected function getIsPrepaidAttribute()
    {
        return (int) $this->prepaid_profile()->exists();
    }

    /**
     * Derive is_standard flag from type_id.
     * @return int
     */
    protected function getIsStandardAttribute(): int
    {
        return (int) ($this->type_id == OfferType::TYPE_STANDARD);
    }

    /**
     * Derive is_seasonal flag from type_id.
     * @return int
     */
    protected function getIsSeasonalAttribute(): int
    {
        return (int) ($this->type_id == OfferType::TYPE_SEASONAL);
    }

    /**
     * Derive is_series flag from type_id.
     * @return int
     */
    protected function getIsSeriesAttribute(): int
    {
        return (int) ($this->type_id == OfferType::TYPE_SERIES);
    }

    /**
     * @return int
     */
    protected function getIsPrepaidSubscriptionAttribute()
    {
        return $this->is_prepaid ? $this->prepaid_profile->is_subscription : 0;
    }

    /**
     * @return Model|HasOne|object|null
     */
    public function getPrepaidProfileAttribute()
    {
        return $this->prepaid_profile()->first();
    }

    /**
     * @return bool
     */
    public function hasPrepaid()
    {
        return (bool) $this->prepaid_profile;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offer_configurations()
    {
        return $this->hasMany(Configuration::class, 'offer_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getOfferConfigurationsAttribute()
    {
        return $this->offer_configurations()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    /**
     * Alias the billing models relationship.
     * @return BelongsToMany
     */
    public function billing_models(): BelongsToMany
    {
        return $this->billingFrequencies();
    }

    /**
     * @return HasMany
     */
    public function offer_billing_models(): HasMany
    {
        return $this->hasMany(OfferBillingModel::class, 'template_id', 'template_id');
    }

    /**
     * @return Collection|null
     */
    protected function getBillingModelsAttribute()
    {
        return $this->billing_models()->get();
    }

    /**
     * @param $billingModelId
     * @return bool
     */
    public function hasBillingModel($billingModelId)
    {
        $ids = [];

        if ($billingModels = $this->getBillingModelsAttribute()) {
            $ids = $billingModels
                ->pluck('id')
                ->toArray();
        }

        return in_array($billingModelId, $ids);
    }

    /**
     * Get the billing model IDs associated with this offer as a flat array.
     * @return array
     */
    public function billingModelIds(): array
    {
        $ids = [];

        if ($billingModels = $this->getBillingModelsAttribute()) {
            $ids = $billingModels
                ->pluck('id')
                ->toArray();
        }

        return $ids;
    }

    /**
     * @param $billingModelId
     * @return bool
     */
    public function isDefaultBillingModel($billingModelId)
    {
        return $this->billing_models()->where('default_flag', 1)->where('id', $billingModelId)->exists();
    }

    /**
     * @param $productId
     * @return bool
     */
    public function hasProduct($productId)
    {
        $ids = [];

        if ($products = $this->products) {
            $ids = $products
                ->pluck('id')
                ->toArray();
        }

        return in_array($productId, $ids);
    }

    /**
     * @param $trialProductId
     * @return bool
     */
    public function hasTrialProduct($trialProductId)
    {
        $ids = [];

        if ($products = $this->products) {
            $ids = $products
                ->filter(function ($product)
                {
                    return $product->is_trial_allowed;
                })
                ->pluck('id')
                ->toArray();
        }

        return in_array($trialProductId, $ids);
    }

    /**
     * @param $productId
     * @return int
     */
    public function productIsBundle($productId)
    {
        $isBundle = 0;

        foreach ($this->getProductsAttribute() as $product) {
            if ($product->id == $productId) {
                $isBundle = $product->is_bundle;
            }
        }

        return $isBundle;
    }

    /**
     * @param $productId
     * @return int
     */
    public function productIsCustomBundle($productId)
    {
        $isCustomBundle = 0;

        foreach ($this->getProductsAttribute() as $product) {
            if ($product->id == $productId) {
                $isCustomBundle = $product->is_custom_bundle;
            }
        }

        return $isCustomBundle;
    }

    /**
     * @return bool
     */
    public function getActiveColumn()
    {
        return false;
    }

    /**
     * @return HasOne
     */
    public function cycle_type()
    {
        return $this->hasOne(CycleType::class, 'id', 'cycle_type_id');
    }

    /**
     * @return Model|HasOne|object|null
     */
    public function getCycleTypeAttribute()
    {
        return $this->cycle_type()->first();
    }

    /**
     * @return HasOne
     */
    public function terminating_cycle_type()
    {
        return $this->hasOne(TerminatingCycleType::class, 'id', 'terminating_cycle_type_id');
    }

    /**
     * Determine if the offer is self recurring.
     * @return bool
     */
    public function isSelfRecurring(): bool
    {
        return $this->cycle_type->id == CycleType::TYPE_SELF;
    }

    /**
     * Determine if the offer is custom recurring.
     * @return bool
     */
    public function isCustomRecurring(): bool
    {
        return $this->cycle_type->id == CycleType::TYPE_CUSTOM;
    }

    /**
     * @return Model|HasOne|object|null
     */
    public function getTerminatingCycleTypeAttribute()
    {
        return $this->terminating_cycle_type()->first();
    }

    /**
     * @return Type|null
     */
    public function getTypeAttribute()
    {
        return OfferType::where('id', $this->type_id)->first();
    }

    /**
     * @return Collection
     */
    public function getTrialAttribute()
    {
        if ($this->is_trial) {
            $isDelayed        = $this->is_delayed_billing;
            $isCustomDuration = !$this->is_trial_duration_inherited;
            $isCustomPrice    = $this->is_trial_custom_price;
            $days             = $this->trial_days;
            $trialPayload     = [
                'is_custom_duration' => $isCustomDuration,
                'days'               => $isCustomDuration ? $days : null,
                'is_custom_price'    => $isCustomPrice,
                'price'              => $isCustomPrice ? $this->trial_price : null,
                'is_delayed_billing' => $isDelayed,
                'delayed_billing'    => [],
            ];

            if ($isDelayed) {
                $isDelayedCustomPrice            = $this->is_delayed_billing_custom_price;
                $trialPayload['delayed_billing'] = [
                    'is_delayed_email_suppressed' => $this->is_delayed_email_suppressed,
                    'is_custom_price'             => $isDelayedCustomPrice,
                    'price'                       => $isDelayedCustomPrice ? $this->delayed_billing_price : null,
                    'days'                        => $this->delayed_billing_days,
                    'default_days'                => $days,
                ];
            }

            return collect($trialPayload);
        }

        return collect([]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cycle_products()
    {
        return $this->hasMany(CycleProduct::class, 'template_id', 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getCycleProductsAttribute()
    {
        if (! $this->getAttribute('is_seasonal')) {
            if (($this->getAttribute('cycle_type_id') == CycleType::TYPE_CUSTOM)) {
                return $this->cycle_products()->get();
            }

            return null;
        }

        return null;
    }

    /**
     * Get the cycle products as an array.
     * @return array
     */
    public function getProductCycleIdsAttribute(): array
    {
        $ids = [];

        if ($products = $this->cycle_products()->get()) {
            if ($products->isNotEmpty()) {
                $ids = array_values(array_filter($products->pluck('product_id')->toArray()));
            }
        }

        return $ids;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getSeasonalProductsAttribute()
    {
        if ($this->getAttribute('is_seasonal')) {
            $products = $this->cycle_products()->get();

            foreach ($products as $product) {
                $product->makeVisible('position');
                $product->makeHidden('cycle_depth');
            }

            return $products;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getSeasonalAttributes()
    {
        if ($this->getAttribute('is_seasonal')) {
            $products = $this->getAttribute('cycle_products');

            return $products;
        }

        return [];
    }

    /**
     * Return the last product that is set if the recurring last cycle rule is
     * set to "Recur to a Product and Hold" (terminating_cycle_type: id = 3).
     * @return HasOne
     */
    public function terminating_cycle_product(): HasOne
    {
        return $this->hasOne(\App\Models\Product::class, 'products_id', 'terminating_product_id');
    }

    /**
     * @return string|null
     */
    public function getTerminatingProductNameAttribute(): ?string
    {
        $this->terminating_cycle_product();
        return $this->terminating_cycle_product->name ?? null;
    }

    /**
     * @return mixed
     */
    public function hasOrders()
    {
        return OrderSubscription::where('offer_id', $this->getAttribute('id'))
            ->exists();
    }

    /**
     * Determine if this offer is of a type that has custom recurring products with available on dates
     * @return bool
     */
    public function hasAvailableOnDates(): bool
    {
        $hasAvailableOnDates = false;

        // For seasonal offers, verify that available on dates are configured.
        // Use this to prevent mixing positional-based and available on date-based seasonal offers.
        //
        if ($this->is_seasonal) {
            if (($cycleProducts = $this->cycle_products()->get()) && $cycleProducts->isNotEmpty()) {
                $cycleProductCount = $cycleProducts->count();
                $dateCount         = 0;

                foreach ($cycleProducts as $cycleProduct) {
                    if ($cycleProduct->start_at_month && $cycleProduct->start_at_day) {
                        $dateCount++;
                    }
                }

                $hasAvailableOnDates = ($cycleProductCount === $dateCount);
            }
        }

        return $hasAvailableOnDates;
    }

    /**
     * Determine if this offer is configured to sync next recurring products based
     * upon available on dates
     * @return bool
     */
    public function shouldSyncAvailableOnDates(): bool
    {
        return $this->hasAvailableOnDates();
    }

    /**
     * Relate trial workflows to the offer.
     * @return BelongsToMany
     */
    public function trialWorkflows(): BelongsToMany
    {
        return $this->belongsToMany(
            TrialWorkflow::class,
            'trial_workflow_offers',
            'offer_id',
            'trial_workflow_id',
            'id',
            'id'
        );
    }

    /**
     * Relate billing_frequency through the billing_subscription_frequency using template_id as a foreign key.
     * @return BelongsToMany
     */
    public function billingFrequencies(): BelongsToMany
    {
        return $this->belongsToMany(
            BillingModel::class,
            'billing_subscription_frequency',
            'template_id',
            'frequency_id',
            'template_id',
            'id'
        );
    }

    /**
     * The billing model discounts that belong to this offer.
     * @return HasMany
     */
    public function billingModelDiscounts(): HasMany
    {
        return $this->hasMany(BillingModelDiscount::class, 'offer_id', 'id');
    }

    /**
     * Get the product ID at a given position.
     * @param int $position
     * @return CycleProduct|null
     */
    public function getCycleProductAtPosition(int $position): ?CycleProduct
    {
        $cycleProduct = null;

        if ($this->isPositionalType()) {
            $cycleProduct = $this->cycle_products()
                ->where('cycle_depth', $position - 1)
                ->first();
        }

        return $cycleProduct;
    }

    /**
     * Get the cycle product at given product ID.
     * @param int $productId
     * @return CycleProduct|null
     */
    public function getCycleProductByProductId(int $productId): ?CycleProduct
    {
        $cycleProduct = null;

        if ($this->isPositionalType()) {
            $cycleProduct = $this->cycle_products()
                ->where('product_id', $productId)
                ->first();
        }

        return $cycleProduct;
    }

    /**
     * * @return Collection|null
     */
    public function fetchProductByAvailableOnDate(): ?Collection
    {
        // Specific query that fetches initial product based upon available on date.
        //
        $query = DB::table('billing_product_template')
            ->select(
                'product_id',
                DB::raw('`cycle_depth` + 1 AS `position`'),
                DB::raw("
                    CONCAT_WS(
                        '-',
                        YEAR(CURDATE()),
                        LPAD(`start_at_month`, 2, '0'),
                        LPAD(`start_at_day`, 2, '0')
                    ) AS `start_at`
                "),
            )
            ->where([
                ['template_id', $this->template_id],
                ['active', 1],
                ['deleted', 0],
            ])
            ->havingRaw('CURDATE() >= start_at')
            ->orderBy('start_at', 'desc')
            ->limit(1);

        if ($data = $query->get()) {
            if ($data->isNotEmpty()) {
                $result = $data->first();
                return new Collection((array) $result);
            }
        }

        return null;
    }

    /**
     * Determine if this offer is of a positional type.
     * @return bool
     */
    public function isPositionalType(): bool
    {
        return in_array($this->type_id, [
            OfferType::TYPE_SEASONAL,
            OfferType::TYPE_SERIES,
        ]);
    }

    /**
     * Get the configured billing model for this offer by billing model ID.
     * @param int $billingModelId
     * @return \App\Models\Offer\BillingModel|null
     */
    public function findAssociatedBillingModel(int $billingModelId): ?OfferBillingModel
    {
        return $this->offer_billing_models()
            ->where('frequency_id', $billingModelId)
            ->first();
    }

    /**
     * The offer details that belong to this offer.
     *
     * @return HasOne
     * @throws \App\Exceptions\CustomModelException
     */
    public function offer_details(): HasOne {
        switch ($this->type_id) {
            case OfferType::TYPE_COLLECTION:
                return $this->hasOne(CollectionOffer::class);
            default:
                throw new CustomModelException('offer.details-unsupported-type');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \App\Exceptions\CustomModelException
     */
    public function getOfferDetailsAttribute()
    {
        return $this->offer_details()->first();
    }

    /**
     * Check if the type id is collection offer.
     *
     * @return bool
     */
    public function isCollectionType(): bool
    {
        return $this->type_id === OfferType::TYPE_COLLECTION;
    }

    /**
     * The supplemental products that belong to this offer.
     *
     * @return HasMany
     */
    public function supplemental_products(): HasMany
    {
        return $this->hasMany(OfferSupplementalProduct::class);
    }

    /**
     * Link to child offer.
     *
     * @return HasOne
     */
    public function linkToChild(): HasOne
    {
        return $this->hasOne(OfferLink::class);
    }

    /**
     * Links to parent offers.
     *
     * @return HasMany
     */
    public function linksToParents(): HasMany
    {
        return $this->hasMany(OfferLink::class, 'linked_offer_id');
    }

    /**
     * @return HasManyThrough
     */
    public function parents(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::class,
            OfferLink::class,
            'linked_offer_id',
            'id',
            'id',
            'offer_id'
        );
    }

    /**
     * @return HasOneThrough
     */
    public function child(): HasOneThrough
    {
        return $this->hasOneThrough(
            self::class,
            OfferLink::class,
            'offer_id',
            'id',
            'id',
            'linked_offer_id'
        );
    }

    /**
     * Build a query with collection offer type where
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsCollectionOffer($query): Builder
    {
        return $query->where('type_id', OfferType::TYPE_COLLECTION);
    }
}
