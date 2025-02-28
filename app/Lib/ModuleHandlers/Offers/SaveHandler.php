<?php

namespace App\Lib\ModuleHandlers\Offers;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ModuleHandlers\ModuleHandlerException;
use App\Models\Product;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Models\Offer\CycleType;
use App\Models\Offer\TerminatingCycleType;
use App\Models\Offer\Offer;
use App\Models\ConfigSetting;
use App\Models\Offer\Type as OfferType;
use App\Facades\SMC;
use App\Models\Offer\Product as BillingOfferProduct;

/**
 * Class SaveHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class SaveHandler extends ModuleHandler
{
    /**
     * @var int $maxCyclesAllowed
     */
    protected int $maxCyclesAllowed = 5;

    /**
     * @var bool $isTrialWorkflowActive
     */
    protected bool $isTrialWorkflowActive = false;

    /**
     * @var bool $isRequestingSeasonal
     */
    protected bool $isRequestingSeasonal = false;

    /**
     * @var bool $isRequestingSeries
     */
    protected bool $isRequestingSeries = false;

    /**
     * @var bool $isRequestingPrepaid
     */
    protected bool $isRequestingPrepaid = false;

    /**
     * @var bool $isRequestingNonStandard
     */
    protected bool $isRequestingNonStandard = false;

    /**
     * @var bool $isRequestingTrial
     */
    protected bool $isRequestingTrial = false;

    /**
     * @var bool $isRequestingMainProducts
     */
    protected bool $isRequestingMainProducts = false;

    /**
     * @var bool $isRequestingNonPrepaidType
     */
    protected bool $isRequestingNonPrepaidType = false;

    protected bool $isCollectionType           = false;

    protected bool $isRequestingStandard       = false;

    protected bool $isRequestingNonCollection  = false;

    /**
     * SaveHandler constructor.
     * @param ModuleRequest $moduleRequest
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        parent::__construct($moduleRequest);

        // Properties that could change the offer's type.
        //
        $this->isRequestingSeasonal     = $this->moduleRequest->has('seasonal') && $this->moduleRequest->get('seasonal');
        $this->isRequestingSeries       = $this->moduleRequest->has('is_series') && $this->moduleRequest->get('is_series');
        $this->isRequestingPrepaid      = $this->moduleRequest->has('prepaid') && $this->moduleRequest->get('prepaid');
        $this->isRequestingTrial        = $this->moduleRequest->has('trial') && $this->moduleRequest->get('is_trial');
        $this->isRequestingMainProducts = $this->moduleRequest->has('products') && $this->moduleRequest->get('products');

        if ($this->moduleRequest->has('type_id')) {
            switch ($this->moduleRequest->get('type_id')) {
                case OfferType::TYPE_STANDARD:
                    $this->isRequestingStandard = true;
                    break;
                case OfferType::TYPE_PREPAID:
                    $this->isRequestingPrepaid = true;
                    break;
                case OfferType::TYPE_SEASONAL:
                    $this->isRequestingSeasonal = true;
                    break;
                case OfferType::TYPE_SERIES:
                    $this->isRequestingSeries = true;
                    break;
                case OfferType::TYPE_COLLECTION:
                    $this->isCollectionType = true;
                    break;
            }
        }

        $this->isRequestingNonStandard = (
            $this->isRequestingSeries ||
            $this->isRequestingSeasonal ||
            $this->isRequestingPrepaid ||
            $this->isCollectionType
        );
        $this->isRequestingNonPrepaidType = (
            $this->isRequestingSeries ||
            $this->isRequestingSeasonal ||
            $this->isCollectionType
        );
        $this->isRequestingNonCollection = (
            $this->isRequestingNonStandard ||
            $this->isRequestingStandard
        ) && !$this->isCollectionType;
    }

    /**
     * Perform core handler function.
     * @throws ModuleHandlerException
     */
    public function performAction(): void
    {
        $this->validateProductsPresent();

        DB::beginTransaction();

        if ($this->resource = $this->generateResource()) {
            $this->resourceId = $this->resource->id;

            // Once a resource is loaded, apply preventatives for existing offers.
            //
            $this->preventLegacyTrial();
            $this->preventOfferTypeSwitching();
            $this->enforceLastCycleType();

            // Save the offer relationships requested.
            //
            $this->saveBillingModelRelationships();
            $this->saveSeriesAttributes();
            $this->saveProductRelationships();
            $this->saveTrialData();
            $this->saveRecurringAttributes();
            $this->savePrepaidAttributes();
            $this->saveSeasonalAttributes();
            $this->saveDefaultCustomRecurringProducts();

            // Reload the resource instance.
            //
            $this->resource->refresh();
        } else {
            throw new ModuleHandlerException(__METHOD__, 'offers.create-resource-failed');
        }

        DB::commit();
    }

    /**
     * Handle the dynamic offer rules array to account for create and update.
     */
    protected function beforeValidation(): void
    {
        // Set max cycles
        //
        if ($config = ConfigSetting::key('BILLING_MODEL_MAX_CYCLES')->first()) {
            if ($max = (int) $config->value) {
                $this->maxCyclesAllowed = $max;
            }
        }

        $this->isTrialWorkflowActive = SMC::check(SMC::TRIAL_WORKFLOW);

        $creating              = !$this->isUpdateExisting;
        $required              = $creating ? 'required|' : 'sometimes|';
        $sometimes             = $creating ? '' : 'sometimes|';
        $customRecurringType   = CycleType::TYPE_CUSTOM;
        $terminateAndHold      = TerminatingCycleType::TYPE_RECUR_TO_PRODUCT_AND_HOLD;
        $this->validationRules = [
            // General
            //
            'name'                                              => "{$required}string|min:1|max:255",

            // Billing Models
            //
            'billing_models'                                    => "{$required}array",
            'billing_models.*.id'                               => "{$required}distinct|int|min:1|exists:mysql_slave.billing_frequency,id",
            'billing_models.*.discount.amount'                  => 'sometimes|required_without:billing_models.*.discount.percent|numeric|min:0',
            'billing_models.*.discount.percent'                 => 'sometimes|required_without:billing_models.*.discount.amount|numeric|min:0|max:100',

            // Legacy Trial
            //
            'is_trial'                                          => 'sometimes|bool',
            'trial'                                             => 'required_if:is_trial,1|array',
            'trial.is_custom_duration'                          => 'sometimes|bool',
            'trial.days'                                        => 'required_if:trial.is_custom_duration,1|int|min:1',
            'trial.is_custom_price'                             => 'sometimes|bool',
            'trial.price'                                       => 'required_if:trial.is_custom_price,1|numeric',
            'trial.is_delayed_billing'                          => 'sometimes|bool',
            'trial.delayed_billing'                             => 'required_if:trial.is_delayed_billing,1|array',
            'trial.delayed_billing.days'                        => 'sometimes|int|min:1',
            'trial.delayed_billing.default_days'                => 'sometimes|required_without:trial.days|int|min:1',
            'trial.delayed_billing.is_delayed_email_suppressed' => 'sometimes|bool',
            'trial.delayed_billing.is_custom_price'             => 'sometimes|bool',
            'trial.delayed_billing.price'                       => 'required_if:trial.delayed_billing.is_custom_price,1|numeric',

            // Trial workflow
            //
            'trial_workflows'              => 'sometimes|required_without:trial',
            'trial_workflows.*.id'         => 'required|int|min:1|exists:mysql_slave.trial_workflows,id',
            'trial_workflows.*.is_default' => 'int|min:0|max:1',

            // Prepaid
            //
            'prepaid'                                           => 'array',
            'prepaid.is_subscription'                           => 'sometimes|bool',
            'prepaid.is_convert_to_standard'                    => 'sometimes|bool',
            'prepaid.is_cancel_immediate'                       => 'sometimes|bool',
            'prepaid.is_refund_allowed'                         => 'sometimes|bool',
            'prepaid.is_initial_shipping_on_restart'            => 'sometimes|bool',
            'prepaid.is_prepaid_shipping'                       => 'sometimes|bool',
            'prepaid.is_prepaid_notification_enabled'           => 'sometimes|bool',
            'prepaid.terms'                                     => 'required_with:prepaid|array',
            'prepaid.terms.*'                                   => 'required_with:prepaid|array',
            'prepaid.terms.*.cycles'                            => 'required_with:prepaid.terms|distinct|int|min:1',
            'prepaid.terms.*.discount_type_id'                  => 'required_with:prepaid.terms|int|min:1|exists:mysql_slave.v_prepaid_discount_types,id',
            'prepaid.terms.*.discount_value'                    => 'sometimes|numeric',

            // Seasonal
            //
            'seasonal'                                          => "sometimes|array",
            'seasonal.*'                                        => "sometimes|array",
            'seasonal.products'                                 => 'required_with:seasonal|array',
            'seasonal.products.*'                               => 'required_with:seasonal|array',
            'seasonal.products.*.id'                            => 'required_with:seasonal|int|min:1|exists:mysql_slave.products,products_id',
            'seasonal.products.*.position'                      => "{$sometimes}required_with:seasonal|required_without:seasonal.products.*.start_at_month|int|min:1",
            'seasonal.products.*.start_at_month'                => "{$sometimes}required_with:seasonal|required_without:seasonal.products.*.position|int|min:1|max:12",
            'seasonal.products.*.start_at_day'                  => "{$sometimes}required_with:seasonal|required_without:seasonal.products.*.position|int|min:1|max:31",

            // Products
            //
            'products'                                          => "{$sometimes}array",

            // Recurring Pieces
            //
            'recurring'                                         => "{$sometimes}required_without_all:seasonal,type_id|array|required_unless:type_id," . implode(',', [OfferType::TYPE_SEASONAL, OfferType::TYPE_COLLECTION]),
            'recurring.terminating_cycle_type_id'               => "{$sometimes}required_without_all:seasonal,prepaid,type_id|int|min:1|exists:mysql_slave.v_offer_terminating_cycle_types,id|required_unless:type_id," . implode(',', [OfferType::TYPE_SEASONAL, OfferType::TYPE_COLLECTION, OfferType::TYPE_PREPAID]),
            'recurring.terminating_product_id'                  => "{$sometimes}required_if:recurring.terminating_cycle_type_id,{$terminateAndHold}|min:1|exists:mysql_slave.products,products_id",
            'recurring.cycle_type_id'                           => "{$sometimes}required_without_all:seasonal,is_series,type_id|int|min:1|exists:mysql_slave.v_offer_cycle_types,id|required_unless:type_id," . implode(',', [OfferType::TYPE_SEASONAL, OfferType::TYPE_COLLECTION, OfferType::TYPE_SERIES]),
            'recurring.is_expire'                               => 'sometimes|bool',
            'recurring.expire_cycles'                           => 'required_if:recurring.is_expire,1|int|min:0',
            'recurring.products'                                => "{$sometimes}required_if:recurring.cycle_type_id,{$customRecurringType}|array",
            'recurring.products.*'                              => "{$sometimes}required_if:recurring.cycle_type_id,{$customRecurringType}|array",
            'recurring.products.*.id'                           => 'required_with:recurring.products|int|min:1|exists:mysql_slave.products,products_id',
            'recurring.products.*.cycle_depth'                  => 'required_with:recurring.products|int',

            // Series Offer type flag
            //
            'is_series'                                         => 'sometimes|bool',

            // !! This is a new parameter for all new offer types to use !!
            'type_id'                                           => 'required_without_all:seasonal,prepaid,is_series,products|int|min:1|exists:mysql_slave.v_offer_types,id',
        ];

        $this->friendlyAttributeNames = [
            'name'                                              => 'Name',
            'type_id'                                           => 'Type ID',
            'billing_models'                                    => 'Billing Models',
            'billing_models.*.id'                               => 'Billing Model ID',
            'billing_models.*.discount.amount'                  => 'Billing Model Flat Discount Amount',
            'billing_models.*.discount.percent'                 => 'Billing Model Discount Percentage',
            'is_trial'                                          => 'Trial Flag',
            'trial'                                             => 'Trial Data',
            'trial.is_custom_duration'                          => 'Custom Trial Duration Flag',
            'trial.days'                                        => 'Custom Trial Duration',
            'trial.is_custom_price'                             => 'Custom Trial Price Flag',
            'trial.price'                                       => 'Custom Trial Price',
            'trial.is_delayed_billing'                          => 'Delayed Billing Flag',
            'trial.delayed_billing'                             => 'Delayed Billing Data',
            'trial.delayed_billing.days'                        => 'Delayed Billing Duration',
            'trial.delayed_billing.default_days'                => 'Delayed Billing Default Duration',
            'trial.delayed_billing.is_delayed_email_suppressed' => 'Delayed Billing Email Suppression Flag',
            'trial.delayed_billing.is_custom_price'             => 'Delayed Billing Custom Price Flag',
            'trial.delayed_billing.price'                       => 'Delayed Billing Price',
            'trial_workflows'                                   => 'Trial Workflow Data',
            'trial_workflows.*.id'                              => 'Trial Workflow ID',
            'trial_workflows.*.is_default'                      => 'Trial Workflow Default Flag',
            'prepaid'                                           => 'Prepaid Data',
            'prepaid.is_subscription'                           => 'Prepaid Subscription Flag',
            'prepaid.is_convert_to_standard'                    => 'Prepaid Convert To Standard Offer Flag',
            'prepaid.is_cancel_immediate'                       => 'Prepaid Immediate Cancellation Flag',
            'prepaid.is_refund_allowed'                         => 'Prepaid Allow Refund Flag',
            'prepaid.is_initial_shipping_on_restart'            => 'Prepaid Initial Shipping On Restart Flag',
            'prepaid.is_prepaid_shipping'                       => 'Prepaid Shipping Flag',
            'prepaid.is_prepaid_notification_enabled'           => 'Prepaid Notification Enabled Flag',
            'prepaid.terms'                                     => 'Prepaid Terms Data',
            'prepaid.terms.*'                                   => 'Prepaid Terms Data',
            'prepaid.terms.*.cycles'                            => 'Prepaid Term Cycles',
            'prepaid.terms.*.discount_type_id'                  => 'Prepaid Term Discount Type ID',
            'prepaid.terms.*.discount_value'                    => 'Prepaid Term Discount Amount',
            'seasonal'                                          => 'Seasonal Data',
            'seasonal.products'                                 => 'Seasonal Products',
            'seasonal.products.*'                               => 'Seasonal Products',
            'seasonal.products.*.id'                            => 'Seasonal Product ID',
            'seasonal.products.*.position'                      => 'Seasonal Product Position',
            'seasonal.products.*.start_at_month'                => 'Seasonal Start At Month',
            'seasonal.products.*.start_at_day'                  => 'Seasonal Start At Day',
            'products'                                          => 'Products',
            'products.*'                                        => 'Products',
            'products.*.id'                                     => 'Product ID',
            'products.*.is_trial_allowed'                       => 'Allow Product As Trial Flag',
            'recurring'                                         => 'Recurring Data',
            'recurring.terminating_cycle_type_id'               => 'Recurring Last Cycle Rule',
            'recurring.terminating_product_id'                  => 'Recurring Last Cycle Product ID',
            'recurring.cycle_type_id'                           => 'Recurring Cycle Type ID',
            'recurring.is_expire'                               => 'Recurring Expire Flag',
            'recurring.expire_cycles'                           => 'Recurring Expire Cycles',
            'recurring.products'                                => 'Recurring Cycle Products',
            'recurring.products.*'                              => 'Recurring Cycle Products',
            'recurring.products.*.id'                           => 'Recurring Cycle Product ID',
            'recurring.products.*.cycle_depth'                  => 'Recurring Product Cycle Depth',
            'is_series'                                         => 'Series Flag',
        ];
    }

    /**
     * Now that data is handled, perform further evaluation.
     * @throws ModuleHandlerException
     */
    protected function afterValidation(): void
    {
        $this->validateTrial();
        $this->validateCustomRecurring();
        $this->validatePrepaidTermsUnique();
        $this->validateUniqueSeasonalDates();
        $this->validateProductRelationships();
    }

    /**
     * Generate the offer resource.
     * @return Model
     */
    protected function generateResource(): Model
    {
        return Offer::create([
            'name'    => $this->moduleRequest->name,
            'type_id' => $this->moduleRequest->get('type_id', OfferType::TYPE_STANDARD),
        ]);
    }

    /**
     * Save the billing_frequency to billing_offer relationships.
     */
    private function saveBillingModelRelationships(): void
    {
        if ($this->moduleRequest->has('billing_models')) {
            $billingModelIds       = [];
            $billingModelDiscounts = [];

            foreach ($this->moduleRequest->billing_models as $billingModelData) {
                $billingModel       = new Collection($billingModelData);
                $billingModelIds[]  = $billingModel->get('id');

                // Prepaid offers cannot have billing model discounts
                //
                if (! $this->isRequestingPrepaid) {
                    $discount           = new Collection($billingModel->get('discount', []));
                    $hasDiscountPercent = $discount->has('percent');
                    $hasDiscountAmount  = $discount->has('amount');

                    if ($hasDiscountPercent || $hasDiscountAmount) {
                        $currentDiscountData = [
                            'frequency_id' => $billingModel->get('id')
                        ];

                        if ($hasDiscountPercent) {
                            $currentDiscountData['percent'] = $discount->get('percent');
                        } else if ($hasDiscountAmount) {
                            $currentDiscountData['amount'] = $discount->get('amount');
                        }

                        $billingModelDiscounts[] = $currentDiscountData;
                    }
                }
            }

            // Save billing model ID relationships.
            //
            if ($this->isUpdateExisting) {
                $this->resource
                    ->billingFrequencies()
                    ->detach();
            }

            $this->resource
                ->billingFrequencies()
                ->attach($billingModelIds);

            // Save billing model discounts.
            //
            if ($this->isUpdateExisting) {
                $this->resource
                    ->billingModelDiscounts()
                    ->delete();
            }

            if ($billingModelDiscounts) {
                $this->resource
                    ->billingModelDiscounts()
                    ->createMany($billingModelDiscounts);
            }
        }
    }

    /**
     * Save the product to offer relationships.
     */
    private function saveProductRelationships(): void
    {
        if ($this->moduleRequest->has('productsRelationships')) {
            $productRelations = $this->moduleRequest->get('productsRelationships');
            $offerId          = $this->resource->id;
            if (count($productRelations)) {
                if ($this->isUpdateExisting) {
                    $this->resource
                        ->products()
                        ->delete();
                }

                foreach (array_chunk($productRelations, 5000) as $relationChunk) {

                    // Prepend offer ID for a new offer request
                    if (! $this->isUpdateExisting) {
                        foreach ($relationChunk as &$relation) {
                            $relation['offer_id'] = $offerId;
                        }
                    }

                    unset($relation);
                    BillingOfferProduct::insert($relationChunk);
                }

            }
        }
    }

    /**
     * Process the trial array in the offer configuration, otherwise handle trial workflows if exists.
     */
    private function saveTrialData(): void
    {
        if (! $this->isRequestingSeasonal && $this->isRequestingTrial) {
            // Top-level trial defaults
            //
            $trial                    = new Collection($this->moduleRequest->trial);
            $isTrialDurationInherited = 0;
            $trialDays                = 0;
            $isCustomPrice            = 0;
            $trialPrice               = 0;

            // Delayed billing defaults
            //
            $isDelayedBilling                = 0;
            $isDelayedBillingEmailSuppressed = 0;
            $isDelayedBillingCustomPrice     = 0;
            $delayedBillingCustomPrice       = 0;
            $delayedBillingDays              = 0;

            // Set defaults is it's an update
            //
            if ($this->isUpdateExisting) {
                $isTrialDurationInherited = $this->resource->is_trial_duration_inherited;
                $trialDays                = $this->resource->trial_days;
                $isCustomPrice            = $this->resource->is_trial_custom_price;
                $trialPrice               = $this->resource->trial_price;

                if ($isDelayedBilling = $this->resource->is_delayed_billing) {
                    $isDelayedBillingEmailSuppressed = $this->resource->is_delayed_email_suppressed;
                    $isDelayedBillingCustomPrice     = $this->resource->is_delayed_billing_custom_price;
                    $delayedBillingCustomPrice       = $this->resource->delayed_billing_price;
                    $delayedBillingDays              = $this->resource->delayed_billing_days;
                }
            }

            // If a custom duration isn't requested, then it will inherit the billing model
            //
            if ($trial->has('is_custom_duration')) {
                $isTrialCustomDuration    = (int) $trial->get('is_custom_duration');
                $isTrialDurationInherited = (int) (! $isTrialCustomDuration);

                if ($isTrialCustomDuration) {
                    $trialDays = (int) $trial->get('days');
                }
            }

            if ($trial->has('is_custom_price')) {
                $isCustomPrice = (int) $trial->get('is_custom_price');
            }

            if ($trial->has('price')) {
                $trialPrice = $trial->get('price');
            }

            if ($trial->has('is_delayed_billing') && ($isDelayedBilling = $trial->get('is_delayed_billing'))) {
                if ($delayedBillingData = $trial->get('delayed_billing')) {
                    $delayedBilling = new Collection($delayedBillingData);

                    if ($delayedBilling->isNotEmpty()) {
                        if ($delayedBilling->has('default_days')) {
                            $delayedBillingDays = (int) $delayedBilling->get('default_days');
                        } else if ($delayedBilling->has('days')) {
                            $delayedBillingDays = (int) $delayedBilling->get('days');
                        }

                        if ($delayedBilling->has('is_delayed_email_suppressed')) {
                            $isDelayedBillingEmailSuppressed = (int) $delayedBilling->get('is_delayed_email_suppressed');
                        }

                        if ($delayedBilling->has('is_custom_price')) {
                            $isDelayedBillingCustomPrice = (int) $delayedBilling->get('is_custom_price');
                        }

                        if ($delayedBilling->has('price')) {
                            $delayedBillingCustomPrice = $delayedBilling->get('price');
                        }
                    }
                }
            }

            // Set legacy trial values on the offer record.
            //
            $this->resource->update([
                'is_trial'                        => 1,
                'is_delayed_billing'              => $isDelayedBilling,
                'is_trial_duration_inherited'     => $isTrialDurationInherited,
                'trial_days'                      => $trialDays,
                'is_trial_custom_price'           => $isCustomPrice,
                'trial_price'                     => $trialPrice,
                'is_delayed_email_suppressed'     => $isDelayedBillingEmailSuppressed,
                'is_delayed_billing_custom_price' => $isDelayedBillingCustomPrice,
                'delayed_billing_price'           => $delayedBillingCustomPrice,
                'delayed_billing_days'            => $delayedBillingDays,
            ]);
        } else if ($this->moduleRequest->has('trial_workflows')) {
            // Save the trial workflow relationships
            //
            if ($trialWorkflowData = $this->moduleRequest->trial_workflows) {
                if ($this->isUpdateExisting) {
                    $this->resource
                        ->trialWorkflows()
                        ->detach();
                }

                // Attach the trial workflow relationships
                //
                $hasDefault = false;

                foreach ($trialWorkflowData as $relationData) {
                    $relation        = new Collection($relationData);
                    $isDefault       = 0;
                    $trialWorkflowId = $relation->get('id');

                    if (! $hasDefault && $relation->has('is_default')) {
                        $hasDefault = (bool) ($isDefault = (int) $relation->get('is_default'));
                    }

                    $this->resource
                        ->trialWorkflows()
                        ->attach($trialWorkflowId, ['is_default' => $isDefault]);
                }
            }
        } else if (! $this->isUpdateExisting || ($this->moduleRequest->has('is_trial') && ! $this->moduleRequest->is_trial)) {
            // There is no trial configuration is it isn't legacy trial or trial workflows.
            // or if API user is explicitely removing trial settings altogether
            //
            $this->resource
                ->trialWorkflows()
                ->detach();
            $this->resource
                ->update(['is_trial' => 0]);
        }
    }

    /**
     * Save the baseline recurring type attributes.
     */
    private function saveRecurringAttributes(): void
    {
        if (! $this->isRequestingSeasonal && $this->moduleRequest->has('recurring')) {
            $recurringData = new Collection($this->moduleRequest->recurring);

            // Update keys where exists.  If it's update then we cannot expect all of them to be there.
            //
            $baseRecurringKeys = [
                'cycle_type_id',
                'terminating_cycle_type_id',
                'terminating_product_id',
                'expire_cycles',
            ];
            $updates = [];

            foreach ($baseRecurringKeys as $baseRecurringKey) {
                if ($recurringData->has($baseRecurringKey)) {
                    $updates[$baseRecurringKey] = $recurringData->get($baseRecurringKey);
                }
            }

            if ($updates) {
                $this->resource->update($updates);
            }
        }
    }

    /**
     * Save the prepaid attributes and relationship.
     */
    private function savePrepaidAttributes(): void
    {
        if ($this->isRequestingPrepaid) {
            $prepaid = new Collection($this->moduleRequest->prepaid);
            $terms   = new Collection($prepaid->get('terms', []));

            if (! $this->isUpdateExisting || ! $this->resource->prepaid_profile) {
                $profile = $this->resource
                    ->prepaid_profile()
                    ->create([
                        'is_subscription'                 => $prepaid->get('is_subscription', 0),
                        'is_convert_to_standard'          => $prepaid->get('is_convert_to_standard', 0),
                        'is_cancel_immediate'             => $prepaid->get('is_cancel_immediate', 0),
                        'is_refund_allowed'               => $prepaid->get('is_refund_allowed', 0),
                        'is_initial_shipping_on_restart'  => $prepaid->get('is_initial_shipping_on_restart', 0),
                        'is_prepaid_shipping'             => $prepaid->get('is_prepaid_shipping', 0),
                        'is_prepaid_notification_enabled' => $prepaid->get('is_prepaid_notification_enabled', 0),
                    ]);

                // Expecting the following keys in $terms: discount_type_id, discount_value, cycles
                //
                $profile
                    ->terms()
                    ->createMany($terms);
            } else {
                // Update existing prepaid profile logic.
                //
                if ($profile = $this->resource->prepaid_profile) {
                    $updates = $prepaid->only([
                        'is_subscription',
                        'is_convert_to_standard',
                        'is_cancel_immediate',
                        'is_refund_allowed',
                        'is_initial_shipping_on_restart',
                        'is_prepaid_shipping',
                        'is_prepaid_notification_enabled',
                    ])->all();

                    if ($updates) {
                        $profile->update($updates);
                    }

                    if ($terms) {
                        $profile
                            ->terms()
                            ->delete();
                        $profile
                            ->terms()
                            ->createMany($terms);
                    }
                }
            }

            if (! $this->isUpdateExisting && ! $this->isRequestingSeasonal && ! $this->isRequestingSeries) {
                // Set last cycle rule to self recurring
                //
                $this->resource->update([
                    'type_id'                   => OfferType::TYPE_PREPAID,
                    'terminating_cycle_type_id' => TerminatingCycleType::TYPE_SELF_RECUR,
                ]);
            }
        }
    }

    /**
     * Save the seasonal relationships and pieces.
     * @throws ModuleHandlerException
     */
    private function saveSeasonalAttributes(): void
    {
        if ($this->isRequestingSeasonal) {
            $seasonal             = new Collection($this->moduleRequest->seasonal);
            $seasonalProducts     = $seasonal->get('products', []);
            $updates              = [
                'type_id'     => OfferType::TYPE_SEASONAL,
                'is_seasonal' => 1,
            ];

            if (! $this->isUpdateExisting) {
                $updates['cycle_type_id']             = CycleType::TYPE_CUSTOM;
                $updates['terminating_cycle_type_id'] = TerminatingCycleType::TYPE_SELF_RECUR;
            }

            if ($seasonalProducts) {
                $cycleProductPayload      = $this->generateCycleProductsPayload($seasonalProducts, OfferType::TYPE_SEASONAL);
                $updates['expire_cycles'] = count($cycleProductPayload);
                $this->saveCycleProductPayload($cycleProductPayload);
            }

            $this->resource
                ->update($updates);
        }
    }

    /**
     * Save attributes specific to series offers.
     */
    private function saveSeriesAttributes(): void
    {
        // Set the type ID for new series offers. If it has prepaid the type will be overwritten here
        //
        if ($this->isRequestingSeries && ! $this->isUpdateExisting) {
            $this->resource->update([
                'type_id'                   => OfferType::TYPE_SERIES,
                'terminating_cycle_type_id' => TerminatingCycleType::TYPE_COMPLETE,
                'cycle_type_id'             => CycleType::TYPE_CUSTOM,
            ]);
        }
    }

    /**
     * Save the default custom recurring product configurations.
     * @throws ModuleHandlerException
     */
    private function saveDefaultCustomRecurringProducts(): void
    {
        if (! $this->isRequestingSeasonal && $this->moduleRequest->has('recurring')) {
            $recurring = $this->moduleRequest->recurring;

            if ($this->resource->cycle_type_id == CycleType::TYPE_CUSTOM) {
                if (isset($recurring['products'])) {
                    $this->saveCycleProductPayload(
                        $this->generateCycleProductsPayload($recurring['products'])
                    );
                }
            }
        }
    }

    /**
     * Save formatted payload as cycle product offer configurations.
     * @param array $payload
     */
    private function saveCycleProductPayload(array $payload)
    {
        if (count($payload)) {
            $ancestorId = 0;
            $parentId   = 0;

            if ($this->isUpdateExisting) {
                $this->resource
                    ->cycle_products()
                    ->forceDelete();
            }

            if ($this->resource->is_trial && $this->isRequestingTrial) {
                $trialProduct = $this->resource
                    ->cycle_products()
                    ->create([
                        'template_id' => $this->resource->template_id,
                        'parent_id'   => $parentId,
                        'ancestor_id' => $ancestorId,
                        'product_id'  => 0,
                        'variant_id'  => 0,
                        'trial_flag'  => 1,
                        'custom_days' => $this->resource->trial_days,
                        'created_by'  => get_current_user_id(),
                    ]);

                $ancestorId = $parentId = $trialProduct->id;
            }

            if (! $ancestorId) {
                $ancestorPayload = $payload[0];
                unset($payload[0]);
                $ancestor = $this->resource
                    ->cycle_products()
                    ->create($ancestorPayload);

                $ancestorId = $parentId = $ancestor->id;
            }

            foreach ($payload as $cycleProduct) {
                $cycleProduct['ancestor_id'] = $ancestorId;
                $cycleProduct['parent_id']   = $parentId;

                $newParent = $this->resource
                    ->cycle_products()
                    ->create($cycleProduct);

                $parentId  = $newParent->id;
            }
        }
    }

    /**
     * Generate the dynamic cycle products payload.
     * @param array $payload
     * @param int $offerType
     * @throws ModuleHandlerException
     * @return array
     */
    private function generateCycleProductsPayload(array $payload, int $offerType = OfferType::TYPE_STANDARD): array
    {
        $translated  = [];
        $cycleDepths = [];
        $user        = get_current_user_id();
        $maxPosition = 1;
        $seasonal    = $offerType == OfferType::TYPE_SEASONAL;

        if ($seasonal) {
            $months = array_column($payload, 'start_at_month');
            $days   = array_column($payload, 'start_at_day');

            // New way of passing seasonal payload
            //
            if (isset($months[0])) {
                array_multisort($months, SORT_ASC, $days, SORT_ASC, $payload);
            }
        }

        foreach ($payload as $i => $product) {
            $depth = $product['cycle_depth'];

            if ($seasonal) {
                if (isset($product['position'])) {
                    $depth = $product['position'] - 1;
                } else {
                    $depth               = $i;
                    $product['position'] = $depth + 1;
                }
            }

            $maxPosition = ($seasonal && ($maxPosition < $product['position']) ? $product['position'] : $maxPosition);

            if (in_array($depth, $cycleDepths)) {
                if ($seasonal) {
                    throw new ModuleHandlerException(__METHOD__, 'offer.invalid-seasonal-positions');
                }

                throw new ModuleHandlerException(__METHOD__, 'offer.invalid-cycle-depths');
            }

            $cycleDepths[]       = $depth;
            $translated[$depth]  = [
                'product_id'     => $product['id'],
                'cycle_depth'    => $depth,
                'template_id'    => $this->resource->template_id,
                'created_by'     => $user,
                'start_at_month' => $product['start_at_month'],
                'start_at_day'   => $product['start_at_day'],
            ];
        }

        if ($seasonal) {
            $count = count($translated);

            if (($count > max($this->maxCyclesAllowed, 12)) || ($count != $maxPosition)) {
                throw new ModuleHandlerException(__METHOD__, 'offer.invalid-seasonal-positions');
            }
        }

        return $translated;
    }

    /**
     * @throws ModuleHandlerException
     */
    private function validateTrial(): void
    {
        // If delayed billing is on, make sure the delayed billing duration is less than trial days
        // 'Delayed Billing Duration must be less than Trial Duration.'
        //
        if ($this->isRequestingTrial) {
            $trial = new Collection($this->moduleRequest->get('trial'));

            if ($isDelayedBilling = $trial->get('is_delayed_billing')) {
                if ($delayedBillingData = $trial->get('delayed_billing', [])) {
                    $delayedBilling     = new Collection($delayedBillingData);
                    $trialDays          = $trial->get('days', 0);
                    $delayedBillingDays = 0;

                    if ($delayedBilling->has('default_days')) {
                        $delayedBillingDays = $delayedBilling->get('default_days');
                    } else if ($delayedBilling->has('days')) {
                        $delayedBillingDays = $delayedBilling->get('days');
                    }

                    if ($delayedBillingDays >= $trialDays) {
                        throw new ModuleHandlerException(__METHOD__, 'offers.delayed-billing-duration-overflow');
                    }
                }
            }

        }
    }

    /**
     * Enforce custom recurring validation business rules.
     * @throws ModuleHandlerException
     */
    private function validateCustomRecurring(): void
    {
        $recurring = new Collection($this->moduleRequest->get('recurring', []));

        if ($recurring->get('cycle_type_id') == CycleType::TYPE_CUSTOM) {
            $products = $recurring->get('products', []);
            $cycles   = count($products);

            // Validate that number of cycles falls within configured max cycles.
            //
            if ($cycles > $this->maxCyclesAllowed) {
                throw (new ModuleHandlerException(__METHOD__, 'offers.max-cycles-exceeded', ['{max}' => $this->maxCyclesAllowed]))
                    ->translateDataToMessage();
            }

            $productIds = [];
            $isSeries   = $this->moduleRequest->get('is_series', 0);

            // Validate that custom cycle products are not custom bundles.
            //
            foreach ($products as $product) {
                $productModel = Product::findOrFail($product['id']);

                if ($productModel->is_custom_bundle) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.custom-cycle-with-custom-bundle');
                }

                if ($isSeries && in_array($product['id'], $productIds)) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.series-custom-cycle-duplicate-product');
                }

                $productIds[] = $product['id'];
            }
        } else if ($this->isRequestingSeries) {
            throw new ModuleHandlerException(__METHOD__, 'offers.invalid-cycle-type-type-series');
        }
    }

    /**
     * Ensure that prepaid cycles are unique.
     * @throws ModuleHandlerException
     */
    private function validatePrepaidTermsUnique(): void
    {
        if ($this->moduleRequest->has('prepaid')) {
            $prepaid = new Collection($this->moduleRequest->prepaid);
            $terms   = $prepaid->get('terms', []);

            if (count($terms)) {
                $cycles = [];

                foreach ($terms as $term) {
                    if (in_array($term['cycles'], $cycles)) {
                        throw new ModuleHandlerException(__METHOD__, 'offer.prepaid-cycles-unique');
                    }

                    $cycles[] = $term['cycles'];
                }
            }
        }
    }

    /**
     * Confirm that seasonal available on dates are unique.
     * @throws ModuleHandlerException
     */
    private function validateUniqueSeasonalDates(): void
    {
        if ($this->moduleRequest->has('seasonal')) {
            $seasonal = new Collection($this->moduleRequest->seasonal['products']);
            $dates    = [];

            foreach ($seasonal as $product) {
                $availableDatesExist = isset($product['start_at_day'], $product['start_at_month']);

                if ($availableDatesExist && $product['start_at_day'] && $product['start_at_month']) {
                    $date = "{$product['start_at_day']}-{$product['start_at_month']}";

                    if (in_array($date, $dates)) {
                        throw new ModuleHandlerException(__METHOD__, 'offers.seasonal-dates-not-unique');
                    }

                    $dates[] = $date;
                } else {
                    throw new ModuleHandlerException(__METHOD__, 'offers.seasonal-dates-required');
                }
            }
        }
    }

    /**
     * Confirm that is products aren't passed then seasonal or series is configured.
     * @throws ModuleHandlerException
     */
    private function validateProductsPresent(): void
    {
        if (! $this->isUpdateExisting) {
            if (! $this->isRequestingMainProducts) {
                if (!$this->isRequestingSeasonal && !$this->isRequestingSeries && !$this->isCollectionType) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.invalid-product-configuration');
                }
            } else if ($this->isRequestingSeasonal || $this->isRequestingSeries) {
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-products-with-type');
            }
        }
    }

    /**
     * Confirm that product relationships are valid.
     * @throws ModuleHandlerException
     */
    private function validateProductRelationships(): void
    {
        if ($this->moduleRequest->has('products')) {
            $products = $this->moduleRequest->get('products');

            $productIds       = [];
            $productRelations = [];
            $uniqueProductIds = [];

            foreach ($products as $productData) {
                if (! isset($productData['id'])) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.invalid-product-id');
                }

                $productId = $productData['id'];

                if (filter_var($productId, FILTER_VALIDATE_INT) === false) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.invalid-product-type');
                }

                if ($productId < 1) {
                    throw new ModuleHandlerException(__METHOD__, 'offers.min-product-length');
                }

                $productIds[]                 = $productId;
                $uniqueProductIds[$productId] = $productId;

                $isTrialAllowed = filter_var($productData['is_trial_allowed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($isTrialAllowed === null) {
                    throw new ModuleHandlerException(__METHOD__,'offers.invalid-allowed-trial-flag');
                }

                $productRelation = [
                    'product_id'       => $productId,
                    'is_trial_allowed' => (int) $isTrialAllowed,
                ];

                if ($this->isUpdateExisting) {
                    $productRelation['offer_id'] = $this->moduleRequest->get('id');
                }

                $productRelations[] = $productRelation;
            }

            if (count($productIds) !== count($uniqueProductIds)) {
                throw new ModuleHandlerException(__METHOD__, "offers.duplicate-product-id");
            }

            $existingProductIdsCount = Product::readOnly()
                ->whereIn('products_id', $productIds)
                ->count();

            if (count($productIds) !== $existingProductIdsCount) {
                $existingProductIds = Product::readOnly()
                    ->whereIn('products_id', $productIds)
                    ->pluck('products_id')
                    ->toArray();

                $invalidProductIds = array_diff($productIds, $existingProductIds);
                throw (
                    new ModuleHandlerException(
                        __METHOD__,
                        'offers.invalid-products',
                        [
                            '{invalidProductIds}' => implode(',', $invalidProductIds)
                        ]
                    )
                )->translateDataToMessage();

            }

            $this->moduleRequest->append('productsRelationships', $productRelations);
        }
    }

    /**
     * Prevent legacy trial with trial workflows.
     * @throws ModuleHandlerException
     */
    private function preventLegacyTrial(): void
    {
        if ($this->isUpdateExisting) {
            // Make sure a user is not attempting to update with trial on an existing offer that
            // does not already have legacy trial configurations with legacy trial configurations.
            //
            if ($this->isTrialWorkflowActive && $this->isRequestingTrial && !$this->resource->is_trial) {
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-trial-configuration');
            }
        } else if ($this->isTrialWorkflowActive && $this->isRequestingTrial) {
            // If trial workflow is active then never allow legacy trial configurations.
            //
            throw new ModuleHandlerException(__METHOD__, 'offers.invalid-trial-configuration');
        }
    }

    /**
     * Prevent a user from switching from one type to another.
     * @throws ModuleHandlerException
     */
    private function preventOfferTypeSwitching(): void
    {
        if ($this->isUpdateExisting) {
            // Current state of the offer loaded.
            //
            $isSeries              = $this->resource->is_series;
            $isSeasonal            = $this->resource->is_seasonal;
            $isStandard            = $this->resource->is_standard;
            $isPrepaid             = $this->resource->typeIsPrepaid();
            $hasPrepaid            = $this->resource->hasPrepaid();
            $isStandardWithPrepaid = $isStandard && $hasPrepaid;
            $isCollection          = $this->resource->isCollectionType();

            if ($isSeries && $this->isRequestingSeasonal) {
                // Prevent the update of a series offer to a seasonal offer. Prepaid is compatible with series.
                //
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-offer-type-series');
            }

            if ($isSeasonal && $this->isRequestingSeries) {
                // Prevent the update of a seasonal offer to a series offer. Prepaid is compatible with seasonal.
                //
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-offer-type-seasonal');
            }

            if ($isPrepaid && $this->isRequestingNonPrepaidType) {
                // Prevent the update of a prepaid offer to any other type.
                //
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-offer-type-prepaid');
            }

            if ($isStandard && ! $isStandardWithPrepaid && $this->isRequestingNonStandard) {
                // Prevent the update of a standard offer to any other type.
                //
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-offer-type-standard');
            }

            if ($isCollection && $this->isRequestingNonCollection) {
                // Prevent the update of a collection offer to any other type.
                //
                throw new ModuleHandlerException(__METHOD__, 'offers.invalid-offer-type-collection');
            }
        }
    }

    /**
     * Make sure that the terminating cycle type ID matches with what is compatible with series offers.
     * @throws ModuleHandlerException
     */
    private function enforceLastCycleType(): void
    {
        $requestedLastCycleRule = null;

        if ($this->isUpdateExisting) {
            $isSeries = (bool) $this->resource->is_series;
        } else {
            $isSeries = $this->isRequestingSeries;
        }

        if ($this->moduleRequest->has('recurring')) {
            $recurring              = new Collection($this->moduleRequest->recurring);
            $requestedLastCycleRule = (int) $recurring->get('terminating_cycle_type_id');

            // For now series offers only compatible with complete terminating cycle type
            //
            if ($isSeries && $requestedLastCycleRule != TerminatingCycleType::TYPE_COMPLETE) {
                throw (
                    new ModuleHandlerException(
                        __METHOD__,
                        'offers.series-wrong-terminating-cycle',
                        [
                            '{wrongId}' => $requestedLastCycleRule,
                            '{goodIds}' => TerminatingCycleType::TYPE_COMPLETE,
                        ]
                    )
                )->translateDataToMessage();
            }
        }
    }
}
