<?php

namespace App\Models\TrialWorkflow;

use App\Models\BaseModel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\HasCreator;
use App\Models\BillingModel\OrderSubscription;

/**
 * Class TrialWorkflowLineItem
 * @package App\Models\TrialWorkflow
 * Map line item to a trial workflow unit
 */
class TrialWorkflowLineItem extends BaseModel
{
   use HasCreator;

   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'trial_workflow_journey_id',
      'trial_workflow_unit_id',
      'order_id',
      'order_type_id',
      'subscription_product_id',
      'subscription_variant_id',
      'subscription_product_price',
      'subscription_product_quantity',
      'billing_model_id',
   ];

   /**
    * @return BelongsTo
    */
   public function journey(): BelongsTo
   {
      return $this->belongsTo(TrialWorkflowJourney::class, 'trial_workflow_journey_id');
   }

   /**
    * Fetch the trial workflow unit for this trial workflow line item
    * @return BelongsTo
    */
   public function unit(): BelongsTo
   {
      return $this->belongsTo(TrialWorkflowUnit::class, 'trial_workflow_unit_id');
   }

   /**
    * Fetch the product for this trial workflow line item
    * @return BelongsTo
    */
   public function subscriptionProduct(): BelongsTo
   {
      return $this->belongsTo(Product::class, 'subscription_product_id');
   }

   /**
    * Fetch the builder for the line item in the billing_model_order table
    * @return Builder
    */
   public function lineItem(): Builder
   {
      return OrderSubscription::where([
         ['id', $this->order_id],
         ['type_id', $this->order_type_id],
      ]);
   }
}
