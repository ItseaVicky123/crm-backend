<?php

namespace App\Models\TrialWorkflow;

use App\Models\Views\ShippingPriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use App\Lib\BillingModels\ShippingPriceCalculationInput;

/**
 * Class TrialWorkflowUnit
 * @package App\Models\TrialWorkflow
 */
class TrialWorkflowUnit extends Model
{
    use LimeSoftDeletes;
    use HasCreator;

    // HasCreator constants
    //
    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    // LimeSoftDeletes constants
    //
    const DELETED_FLAG = 'is_deleted';
    const ACTIVE_FLAG  = 'is_active';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'trial_workflow_id',
        'step_number',
        'name',
        'duration',
        'price',
        'quantity',
        'is_parent_cancellable',
        'product_id',
        'is_shippable',
        'is_notifiable',
        'is_one_time_pairable',
        'shipping_price_type_id',
        'shipping_price',
        'created_by',
        'updated_by',
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
     * @return HasMany
     */
    public function audits(): HasMany
    {
        return $this->hasMany(TrialWorkflowUnitAudit::class, 'trial_workflow_unit_id');
    }

    /**
     * @return BelongsTo
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(TrialWorkflow::class, 'trial_workflow_id');
    }

    /**
     * Check that price is a numeric value
     * @return bool
     */
    public function isPriceSet(): bool
    {
        return is_numeric($this->price);
    }

    /**
     * Check if quantity was set on this unit
     * @return bool
     */
    public function isQuantitySet(): bool
    {
        return ! is_null($this->quantity);
    }

    /**
     * Check for custom duration, system should inherit from billing model if not set
     * @return bool
     */
    public function hasDuration(): bool
    {
        return $this->duration > 0;
    }

    /**
     * Check for custom product ID
     * @return bool
     */
    public function hasCustomProduct(): bool
    {
        return $this->product_id > 0;
    }

    /**
     * Determine whether or not the unit should delay paired product attachment
     * @return bool
     */
    public function shouldDelayPairedProduct(): bool
    {
        return ! $this->is_one_time_pairable;
    }

    /**
     * @param ShippingPriceCalculationInput $input
     * @return float
     */
    public function getUnitShippingPrice(ShippingPriceCalculationInput $input): float
    {
        $price = $input->getDefaultPrice();

        switch ($this->shipping_price_type_id) {
            case ShippingPriceType::INITIAL:
                $price = $input->getInitialAmount();
            break;
            case ShippingPriceType::SUBSCRIPTION:
                $price = $input->getSubscriptionAmount();
            break;
            case ShippingPriceType::CUSTOM:
                $price = (float) $this->shipping_price;
            break;
        }

        return $price;
    }
}
