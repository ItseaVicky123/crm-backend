<?php

namespace App\Models\OrderAttributes;

use App\Exceptions\OrderAttributeImmutableException;
use App\Facades\SMC;
use App\Models\Order;
use App\Models\OrderAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Class ConsentWorkflowType
 * Order Attribute used to store the type of consent workflow that should be used for the related order
 *
 * @package App\Models\OrderAttributes
 *
 * @method static Builder|ConsentWorkflowType controlledBy()
 * @method static Builder|ConsentWorkflowType internallyControlled()
 * @method static Builder|ConsentWorkflowType paysafeControlled()
 */
class ConsentWorkflowType extends OrderAttribute
{
   public const TYPE_ID       = 32;
   public const DEFAULT_VALUE = 1;

   /**
    * Consent type indicating that we will internally handle all stages of the consent workflow
    */
   public const INTERNAL_CONSENT_TYPE = 'Internal';

   /**
    * Consent type indicating that paysafe will handle the re-bill consent workflow
    */
   public const PAYSAFE_CONSENT_TYPE  = 'Paysafe';

    /**
     * @var array
     */
    protected $attributes = [
        'type_id' => self::TYPE_ID,
    ];

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopePaysafeControlled(Builder $query): Builder
    {
       return $this->scopeControlledBy($query, self::PAYSAFE_CONSENT_TYPE);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeInternallyControlled(Builder $query): Builder
    {
        return $this->scopeControlledBy($query, self::INTERNAL_CONSENT_TYPE);
    }

    /**
     * @param Builder $query
     * @param string  $consentType One of the self: X_CONSENT_TYPE constants
     * @return Builder
     */
    public function scopeControlledBy(Builder $query, string $consentType): Builder
    {
        return $query->where('value', $consentType);
    }

   /**
    * @param int|string|Order $order
    * @param string|null      $value
    * @param bool             $ignoreDuplicate
    * @return mixed
    */
   public static function createForOrder($order, $value = null, $ignoreDuplicate = false)
   {
       $isObject    = $order instanceof Order;
       $orderObj    = $isObject ? $order : Order::find($order);
       $orderId     = $isObject ? $order->id : (string) $order;
       $gatewayId   = $isObject ? $orderObj->gateway_id : '';
       $isPaysafe   = $orderObj && $orderObj->is_paysafe_gateway;
       $gatewayText = $isPaysafe ? 'paysafe' : 'non paysafe';
       $type        = null;
      if (!$value) {
         $value = SMC::check(SMC::NMI_PAYSAFE_CONTROLLED_CONSENT) && $isPaysafe ? self::PAYSAFE_CONSENT_TYPE : self::INTERNAL_CONSENT_TYPE;
      }
       \fileLogger::log_flow(
           __METHOD__ . " CONSENT WORKFLOW: creating $value consent workflow type for $orderId " .
           " using a currently $gatewayText gateway with gateway id $gatewayId"
       );
      try
      {
         $type = parent::createForOrder($order, $value);
      }
      catch (OrderAttributeImmutableException $oaie)
      {
          \fileLogger::log_flow(__METHOD__. " CONSENT WORKFLOW: ConsentRequired already exists for order {$orderId}");
      }
      return $type;
   }
}
