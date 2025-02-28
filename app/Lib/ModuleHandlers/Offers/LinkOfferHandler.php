<?php

namespace App\Lib\ModuleHandlers\Offers;

use App\Exceptions\CustomModelException;
use App\Models\BillingModel\BillingModel;
use App\Models\Offer\Offer;
use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Offer\OfferLink;
use App\Models\Order;
use App\Models\OrderAttributes\Announcement;
use App\Models\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Lib\ApiResponder;
use Illuminate\Http\Response;

class LinkOfferHandler
{
   use ApiResponder;

   protected Offer         $offer;
   protected ModuleRequest $request;

   /**
    * @param int $offerId
    * @param array $request
    */
   public function __construct(int $offerId, array $request = [])
   {
      $this->request = new ModuleRequest($request);
      $this->offer = Offer::findOrFail($offerId);
   }

   /**
    * @throws \App\Exceptions\CustomModelException
    */
   public function link()
   {
      if (! $this->offer->isCollectionType()) {
         throw new CustomModelException('offer.collection-offer.incorrect-type');
      }
      try {
         $requestAll = $this->request->all();
         $this->request->validate([
             'linked_offer_id'          => "required|int|min:1|exists:" . Offer::class . ",id",
             'billing_model_id'         => "required|int|min:1|exists:" . BillingModel::class . ",id",
             'rebill_depth'             => "required|int|min:0",
             'announce_days_in_advance' => "required|int|min:1",
             'announce_day_of_week'     => "required|int|between:0,6", //Sunday through Saturday
         ], $requestAll);
      } catch (ValidationException $e) {
         return $this->abortWithCode(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'offer.collection-offer.invalid-request',
            $e->errors()
         );
      }

      $offerToBeLinked = Offer::find($this->request->get('linked_offer_id'));

      if (! $offerToBeLinked->isCollectionType()) {
         throw new CustomModelException('offer.collection-offer.incorrect-type');
      }

      // You cannot link to the offer that has a parent offer
      if ($this->offer->linksToParents()->exists()) {
         throw new CustomModelException('offer.already-linked-by-another');
      }

      // You cannot link offer that has a child linked to it
      if ($offerToBeLinked->linkToChild()->exists()) {
         throw new CustomModelException('offer.link-offer-has-linked-offer');
      }

      if (! isset($requestAll['is_enable_postcard'])) {
          $requestAll['is_enable_postcard'] = 0;
      }
      $this->offer->linkToChild()->updateOrCreate([], $requestAll);

      return $this->response($this->offer->linkToChild->refresh());
   }

   /**
    * @throws \App\Exceptions\InvalidIdException
    */
   public function unlink()
   {
      $this->offer->linkToChild()->delete();

      return $this->response();
   }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getNextAnnouncementOrders(): Collection
    {
        $offerLinks           = OfferLink::where('announce_day_of_week', now()->dayOfWeek)
            ->where('is_enable_postcard', true)
            ->get();
        $recurringItemsToSend = [];

        foreach ($offerLinks as $offerLink) {
            $daysToAnnounce = $offerLink->announce_days_in_advance;
            // Set start day from today, to grab anything we have possibly missed
            $recurFrom = now()->startOfDay()->format('Y-m-d H:i:s');
            // Since we send announcements weekly, add 1 week without a day and this will be the timeframe that we will announce for
            $recurTo   = now()->addDays($daysToAnnounce)->addWeek()->subDay()->endOfDay()->format('Y-m-d H:i:s');

            // Orders that have announcement scheduled
            // And have at least one recurring item withing the date range
            $orders = Order::whereHas('announcement', fn($q) => $q->where('value', Announcement::SCHEDULED))
                ->whereRaw("(
                    recurring_date BETWEEN ? AND ?
                    AND is_recurring = 1
                    AND is_hold = 0
                    AND orders_status IN (?, ?, ?)
                    OR EXISTS (
                        SELECT *
                        FROM upsell_orders
                        WHERE orders.orders_id = upsell_orders.main_orders_id
                        AND recurring_date BETWEEN ? AND ?
                        AND deleted = 0
                        AND is_recurring = 1
                        AND is_hold = 0
                        AND orders_status IN (?, ?, ?)
                    )
                )", [
                    $recurFrom,
                    $recurTo,
                    OrderStatus::STATUS_APPROVED,
                    OrderStatus::STATUS_VOID,
                    OrderStatus::STATUS_SHIPPED,
                    $recurFrom,
                    $recurTo,
                    OrderStatus::STATUS_APPROVED,
                    OrderStatus::STATUS_VOID,
                    OrderStatus::STATUS_SHIPPED,
                ])
                ->get();

            foreach ($orders as $order) {
                $subscriptions = $order
                    ->allSubscriptions()
                    // Double sure that we ar taking to consideration only those child subscriptions
                    // that have their announcement day of week set for today
                    ->where('parent.offer.linkToChild.announce_day_of_week', now()->dayOfWeek)
                    // Filter out subscriptions that are not related to this particular child/parent offer ID
                    ->where('offer_id', $offerLink->linked_offer_id)
                    ->where('parent.offer_id', $offerLink->offer_id);

                foreach ($subscriptions as $subscription) {
                    $eligibleLineItems = $subscription
                        ->activeRecurringItems()
                        // Make sure we are only getting items withing the date range
                        ->whereBetween('recurring_date', [
                            $recurFrom,
                            $recurTo,
                        ])
                        // Make sure that the main order ID matches to the one we have found in the main query
                        // This is needed because when we fetch child subscription, it might be from a different order ID
                        ->where('order_id', $order->order_id);

                    $recurringItemsToSend[$offerLink->offer_id][$order->order_id][$subscription->id] = $eligibleLineItems;
                }
            }
        }

        return collect($recurringItemsToSend);
    }
}
