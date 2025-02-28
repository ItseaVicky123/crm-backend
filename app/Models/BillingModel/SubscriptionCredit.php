<?php

namespace App\Models\BillingModel;

/**
 * These Credits are only available
 * to orders that were placed
 * using Billing Models & Offers
 *
 * Class SubscriptionCredit
 * @package App\Models\BillingModel
 */
class SubscriptionCredit extends Subscription
{
   /**
    * @var array
    */
   protected $fillable = [
      'available_credit',
   ];

    /**
     * Used to standardize output of SubscriptionController->showSubscriptionCredit() between legacy and DNVB.
     * Transform my JSON output to look like a legacy \App\Models\Credit instance.
     *
     * @return array
     */
   public function transformToLegacyCreditModelFormat(): array
   {
       // Need a dummy value for the 'type' property.
       // Actual legacy models used 1 and 2 for this value so use something far away from those real values.
       $dummyTypeValue = 9999;

       return [
           'item_id'    => $this->subscription_orders()->first()->order->subscription_id,
           'amount'     => $this->available_credit,
           'created_at' => $this->date_in,
           'updated_at' => ($this->update_in ?? $this->date_in),
           'type'       => ['id' => $dummyTypeValue, 'name' => 'BillingModelSubscriptionCredit'],
           'creator'    => ['id' => $this->created_by, 'name' => '', 'email' => '', 'is_active' => 1, 'department_id' => 0, 'call_center_provider_id' => 0],
           'updator'    => ['id' => $this->updated_by, 'name' => '', 'email' => '', 'is_active' => 1, 'department_id' => 0, 'call_center_provider_id' => 0]
       ];
   }
}
