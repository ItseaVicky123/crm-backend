<?php

namespace App\Lib\DeclineRetry;

use App\Models\DeclineManager\SmartProfile;
use App\Models\DeclineRetry\DeclineRetryJourney;
use App\Models\Order;
use App\Models\OrderHistoryNote;
use App\Models\OrderAttributes;
use App\Models\OrderAttribute;
use App\Models\Upsell;
use App\Services\SmartRetry\Client;
use decline_salvage\handler;


/**
 * Class AbstractContext
 * @package App\Services\Dunning
 */
class AbstractContext
{
    public const DUPLICATE_MAX_SECONDS = 1200;
    public const REBILL_TYPE_INITIALS  = 1;
    public const REBILL_TYPE_REBILLS   = 2;
    public const REBILL_TYPE_ALL       = 3;

    /**
     * @var int $currentOrderId
     */
    protected int $currentOrderId;

    /**
     * @var handler|null
     */
    protected ?handler $salvageClient = null;

    /**
     * @var Client|null
     */
    protected ?Client $smartRetryClient = null;

    /**
     * @var bool $isSmartRetriesEnabled
     */
    protected bool $isSmartRetriesEnabled;

    /**
     * @var bool $isSmartRetry
     */
    protected bool $isSmartRetry = false;

    /**
     * @var float|null $processPercent
     */
    protected ?float $processPercent = null;

    /**
     * @var int|null $maxDays
     */
    protected ?int $maxDays;

    /**
     * @var int $newOrderGatewayId
     */
    protected int $newOrderGatewayId;

    /**
     * @var int $newOrderGatewayAccountId
     */
    protected int $newOrderGatewayAccountId;

    /**
     * @var array $orderIds
     */
    protected array $orderIds;

    /**
     * @var int $systemAdminId
     */
    protected int $systemAdminId = 999999;

    /**
     * @var int $duplicateSecondsWindow
     */
    protected int $duplicateSecondsWindow = 20;

    /**
     * @var int $targetOrderId
     */
    protected int $targetOrderId;

    /**
     * @var Order $targetOrderModel
     */
    protected Order $targetOrderModel;

     /**
      * @var int $retryTypeId
      */
    protected int $retryTypeId = DeclineRetryJourney::RETRY_TYPE_RULE_BASED;

    /**
     * AbstractContext constructor.
     * @param int $targetOrderId
     */
    public function __construct(int $targetOrderId)
    {
        $this->targetOrderId = $targetOrderId;

        try {
            $this->targetOrderModel = Order::withoutGlobalScopes()->find($this->targetOrderId);
            $this->loadDuplicateSecondsWindow();
            $this->destroyDuplicates();
        } catch (\Exception $e) {
            \fileLogger::notification("Exception caught while processing: {$e->getMessage()}", __METHOD__, LOG_ERROR);
        }
    }

    /**
     * Stub function - define in extensions
     */
    protected function loadDuplicateSecondsWindow(): void
    {}

    /**
     * Setup decline manager/smart retry handlers
     */
    protected function initializeClients(): void
    {
        // Determine new order gateway
        //
        $newOrderGatewayData            = \get_new_order_gateway_info($this->targetOrderId);
        $this->newOrderGatewayId        = $newOrderGatewayData['gateway'];
        $this->newOrderGatewayAccountId = $newOrderGatewayData['account'];

        // Initialize the default salvage handler
        //
        $this->salvageClient = new handler;
        $this->salvageClient->set_stamp(strtotime(date('Y-m-d')));
        $this->salvageClient->load_default();
        $this->processPercent = $this->salvageClient->get('process_pct');
        $this->maxDays        = $this->salvageClient->get('max_days');

        // Initialize the smart retry client if platform instance is configured to do so
        //
        if ($this->isSmartRetriesEnabled = (\vas_enabled('SMART_RETRIES') && SmartProfile::first())) {
            $this->retryTypeId      = DeclineRetryJourney::RETRY_TYPE_SMART;
            $this->smartRetryClient = Client::make();
        }
    }

   /**
    * Destroy initial dunning order attributes for duplicate declines coming in within a certain time frame
    * We will take the latest initial attempt afterwards
    * @return int
    */
   protected function destroyDuplicates(): int
   {
      $deletedRows = 0;

      if ($this->targetOrderModel) {
         $email     = $this->targetOrderModel->email;
         $firstName = $this->targetOrderModel->first_name;
         $lastName  = $this->targetOrderModel->last_name;
         $from      = date('Y-m-d H:i:s', strtotime("-{$this->duplicateSecondsWindow} seconds"));
         $to        = date('Y-m-d H:i:s');
         $builder   = Order::withoutGlobalScopes()
            ->where([
               ['orders_id', '!=', $this->targetOrderId],
               ['customers_email_address', $email],
               ['delivery_fname', $firstName],
               ['delivery_lname', $lastName],
               ['orders_status', 7]
            ])
            ->whereBetween('t_stamp', [$from, $to]);

         if ($builder->count()) {
            $orderIds = [];

            // There are duplicates in a short window of time
            //
            if ($data = $builder->get()) {
               foreach ($data as $dupeOrder) {
                  $orderIds[] = $dupeOrder->orders_id;
                  OrderHistoryNote::create([
                     'order_id'   => $dupeOrder->orders_id,
                     'message'    => "Disabling initial dunning on this decline order because a duplicate transaction was processed within {$this->duplicateSecondsWindow} seconds of this one.",
                     'type_name'  => 'initial-dunning-disabled',
                     'author'     => $this->systemAdminId,
                  ]);
               }

               // Delete the order attributes related to these orders so that they don't retry
               //
               $deletedRows = OrderAttribute::where([['type_id', OrderAttributes\InitialDunning::TYPE_ID]])
                  ->whereIn('order_id', $orderIds)
                  ->delete();

               // Set them to non recurring
               //
               $orderUpdateData = ['is_recurring' => 0];
               $orderIdCsv      = implode(', ', $orderIds);

               if (Order::whereIn('orders_id', $orderIds)->update($orderUpdateData)) {
                  \fileLogger::notification("Turned recurring OFF for order IDs: {$orderIdCsv}", __METHOD__, LOG_WARN, '', '', false);
                  // Do the same for any related upsells
                  //
                  if (Upsell::whereIn('main_orders_id', $orderIds)->update($orderUpdateData)) {
                     \fileLogger::notification("Turned recurring OFF for upsells related to order IDs: {$orderIdCsv}", __METHOD__, LOG_WARN, '', '', false);
                  }
               }
            }
         }
      }

      return $deletedRows;
   }
}