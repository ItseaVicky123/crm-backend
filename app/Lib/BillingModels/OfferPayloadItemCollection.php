<?php

namespace App\Lib\BillingModels;

use Illuminate\Support\Collection;
use \array_accessor;
use billing_models\offer;

/**
 * Helper collection for processing some offer-related request data
 * Class OfferPayloadItemCollection
 * @package App\Lib\BillingModels
 */
class OfferPayloadItemCollection extends Collection
{
   /**
    * @var array_accessor $payload
    */
   private array_accessor $payload;

   /**
    * @var int $productId
    */
   private int $productId;

   /**
    * @var Collection|null $trialNode
    */
   private ?Collection $trialNode = null;

   /**
    * @var Collection|null $trialVariantNode
    */
   private ?Collection $trialVariantNode = null;

   /**
    * @var offer|null $offer
    */
   private ?offer $offer = null;

   /**
    * @var int|null $trialProductId
    */
   private ?int $trialProductId = null;

   /**
    * @var int|null $trialVariantId
    */
   private ?int $trialVariantId = null;

   /**
    * @var int|null $initialPosition
    */
   private ?int $initialPosition = null;

   /**
    * @var bool $useTrialWorkflow
    */
   private bool $useTrialWorkflow = false;

   /**
    * OfferPayloadItemCollection constructor.
    * @param array_accessor $payload
    * @param int $defaultBillingModelId
    */
   public function __construct(array_accessor $payload, int $defaultBillingModelId)
   {
      parent::__construct([
         'product_id'            => $payload->get_int('product_id'),
         'offer_id'              => $payload->get_int('offer_id'),
         'configuration_id'      => $payload->get_int('offer_configuration_id', null),
         'billing_model_id'      => $payload->get_int('billing_model_id', $defaultBillingModelId),
         'prepaid_cycles'        => $payload->get_int('prepaid_cycles'),
         'current_prepaid_cycle' => $payload->get_int('current_prepaid_cycle'),
         'prepaid_price'         => $payload->get('prepaid_price', null),
         'preserve_price'        => $payload->get_int('preserve_price'),
         'price'                 => $payload->get('price', null),
         'step_num'              => $payload->get('step_num', null),
         'quantity'              => $payload->get_int('quantity', 1),
         'variant'               => $payload->get('variant', null),
         'options'               => $payload->get('options', null),
         'fixed_shipping_amount' => $payload->get('fixed_shipping_amount', null),
         'shipping_method_id'    => $payload->get('shipping_method_id', null),
      ]);
      $this->payload   = $payload;
      $this->productId = $payload->get_int('product_id');

      // Check for trial workflow ahead of time, so we can make work with other offer types
      //
      $trial = $this->payload->get('trial', []);

      if (isset($trial['use_workflow'])) {
         $this->useTrialWorkflow = (bool) $trial['use_workflow'];
      }
   }

   /**
    * Check for positional data and set it to this collection
    */
   public function processPosition(): void
   {
      // Positional-based offers will have this
      //
      if ($this->payload->exists('position')) {
         $this->initialPosition = $this->payload->get_int('position');
         $this->put('initial_position', $this->initialPosition);
      }
   }

   public function loadBundleChildren(): void
   {
      if ($this->payload->exists('children')) {
         $this->put('children', $this->payload->get('children'));
      }
   }

   /**
    * @return array_accessor
    */
   public function getPayload(): array_accessor
   {
      return $this->payload;
   }

   /**
    * Process trial node and set cycle values
    */
   public function processTrial(): void
   {
      // Initialize trial and children params from input payload
      //

      // Massage some of the trial data if it was passed in
      //
      if ($trialNodeData = $this->payload->get('trial', null)) {
         $this->put('trial', $trialNodeData);
         $this->trialNode      = Collection::make($trialNodeData);
         $this->trialProductId = (int) $this->trialNode->get('product_id', null);

         // Set trial quantity to 1 if it was not passed in
         //
         if ($this->trialProductId && !$this->trialNode->has('quantity')) {
            $this->trialNode->put('quantity', 1);
         }

         // Process trial variant node
         //
         $trialVariantData = null;

         if ($this->trialNode->has('variant')) {
            // If API passed in the whole variant array, pass it
            //
            $trialVariantData = $this->trialNode->get('variant');
         } else if ($this->trialNode->has('variant_id')) {
            // If API passed in the variant ID only, create a barebones array
            //
            $this->trialVariantId = (int) $this->trialNode->get('variant_id');
            $trialVariantData = ['variant_id' => $this->trialVariantId];
         }

         if (! is_null($trialVariantData)) {
            $this->trialVariantNode = Collection::make($trialVariantData);
         }
      }

   }

   /**
    * @return bool
    */
   public function hasPosition(): bool
   {
      return !is_null($this->initialPosition);
   }
   /**
    * @return bool
    */
   public function hasTrial(): bool
   {
      return (bool) $this->trialNode;
   }

   /**
    * @return bool
    */
   public function isUsingTrialWorkflow(): bool
   {
      return $this->useTrialWorkflow;
   }

   /**
    * @return bool
    */
   public function hasTrialVariant(): bool
   {
      return (bool) $this->trialVariantNode;
   }

   /**
    * @return Collection
    */
   public function getTrialVariant(): Collection
   {
      return $this->trialVariantNode;
   }

   /**
    * @return int|null
    */
   public function getTrialProductId(): ?int
   {
      return $this->trialProductId;
   }

   /**
    * @return int|null
    */
   public function getTrialVariantId(): ?int
   {
      return $this->trialVariantId;
   }

   /**
    * @return int|null
    */
   public function getInitialPosition(): ?int
   {
      return $this->initialPosition;
   }

   /**
    * @param int $id
    */
   public function setTrialVariantId(int $id)
   {
      $this->trialVariantId = $id;
      $this->trialVariantNode->put('variant_id', $id);
   }

    /**
     * @param int $id
     */
    public function setTrialVariantIdInArray(int $id)
    {
        $trialNode            = $this->get('trial', []);
        $trialNode['variant'] = ['variant_id' => $id];
        $this->put('trial', $trialNode);
    }

   /**
    * @param int $id
    */
   public function setProductId(int $id): void
   {
      $this->productId = $id;
      $this->put('product_id', $id);
   }

   /**
    * @param int $position
    */
   public function setInitialPosition(int $position): void
   {
      // Positions start at 1, cycle_depth begins at 0
      // cycle depth will get overwritten if trial workflow
      //
      $this->initialPosition = $position;
      $this->put('cycle_depth', $position - 1);
      $this->put('initial_position', $position);
   }
}
