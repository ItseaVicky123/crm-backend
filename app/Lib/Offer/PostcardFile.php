<?php

namespace App\Lib\Offer;

use App\Models\Offer\OfferLink;
use App\Models\Order;
use App\Models\OrderAttributes\Announcement;
use fileLogger;
use App\Lib\ModuleHandlers\Offers\LinkOfferHandler;
use App\Lib\Offer\PostCardUploadHandler;
use App\Lib\Orders\NextRecurringOrderPriceCalculator;

/**
 * Class PostcardFile
 *
 * @package App\Lib\Offer
 */
class PostcardFile
{
    protected $csvDataOfferWise       = [];

    protected $NextAnnouncementOrders = [];

    public function __construct()
    {
        $this->NextAnnouncementOrders = LinkOfferHandler::getNextAnnouncementOrders();
        $csvDetails                   = [];

        foreach ($this->NextAnnouncementOrders as $offer_id => $orderGroups) {
            foreach ($orderGroups as $order_id => $subscriptions) {
                foreach ($subscriptions as $subscription_id => $lineItems) {
                    $OrderPriceCalculator = (new NextRecurringOrderPriceCalculator($order_id))->setLineItemsList($lineItems);
                    $OrderPriceCalculator->calculate();

                    $row = [
                        'ProductSKU'      => [],
                        'OrderId'         => '',
                        'FirstName'       => '',
                        'LastName'        => '',
                        'Address1'        => '',
                        'Address2'        => '',
                        'City'            => '',
                        'State'           => '',
                        'Zip'             => '',
                        'Email'           => '',
                        'RecurringDate'   => '',
                        'SubtotalWithTax' => $OrderPriceCalculator->getSubtotalAmount() + $OrderPriceCalculator->getTaxAmount(),
                        'Shipping'        => $OrderPriceCalculator->getShippingTotalAmount(),
                        'Total'           => $OrderPriceCalculator->getTotalAmount(),
                    ];

                    foreach ($OrderPriceCalculator->getNextLineItems() as $nextLineItem) {
                        $lineItem = $nextLineItem->getLineItem();

                        $row['ProductSKU'][]  = $nextLineItem->getProduct()->sku;
                        $row['OrderId']       = $order_id;
                        $row['FirstName']     = $lineItem->first_name;
                        $row['LastName']      = $lineItem->last_name;
                        $row['Address1']      = $lineItem->address;
                        $row['Address2']      = $lineItem->address2;
                        $row['City']          = $lineItem->city;
                        $row['State']         = $lineItem->state;
                        $row['Zip']           = $lineItem->zip;
                        $row['Email']         = $lineItem->email;
                        $row['RecurringDate'] = $lineItem->next_valid_recurring_date->format('m/d/Y');
                    }

                    if (! empty($row['OrderId'])) {
                        fileLogger::log_flow(__METHOD__." Order id ".$order_id." picked for postcard CSV");
                        $csvDetails[$offer_id][$order_id][] = [
                            implode(', ', $row['ProductSKU']),
                            $row['OrderId'],
                            $row['FirstName'],
                            $row['LastName'],
                            $row['Address1'],
                            $row['Address2'],
                            $row['City'],
                            $row['State'],
                            $row['Zip'],
                            $row['Email'],
                            $row['RecurringDate'],
                            $row['SubtotalWithTax'],
                            $row['Shipping'],
                            $row['Total'],
                        ];
                    } else {
                        fileLogger::log_flow(__METHOD__." Order id ".$order_id." not picked for postcard CSV");
                    }
                }
            }
        }

        $this->csvDataOfferWise = $csvDetails;
    }

    /**
     * create postcard files and upload into ftp provider.
     *
     */
    public function createPostcardCSVFile()
    {
        try {
            $csvDataOfferWiseArr = $this->csvDataOfferWise;
            if (count($csvDataOfferWiseArr)) {
                foreach ($csvDataOfferWiseArr as $offer_id => $csvDataOfferWise) {
                    $postCardFileUrlParams = [];

                    $fileName = storage_path('logs/').sprintf('postcard-for-%s-%s.csv', $offer_id, date('Y-m-d-H-i-s'));
                    $handle   = fopen($fileName, 'w');

                    $titleArr = [
                        'Product SKU',
                        'Order Id',
                        'First Name',
                        'Last Name',
                        'Address 1',
                        'Address 2',
                        'City',
                        'State',
                        'Zip',
                        'Email',
                        'Recurring Date',
                        'Subtotal with Tax',
                        'Shipping',
                        'Total',
                    ];
                    fputcsv($handle, $titleArr);

                    $orderIds = [];

                    foreach ($csvDataOfferWise as $order_id => $subscriptions) {
                        foreach ($subscriptions as $row) {
                            fputcsv($handle, $row);
                        }

                        $orderIds[] = $order_id;
                    }
                    fclose($handle);

                    $postCardFileUrlParams[$offer_id] = $fileName;
                    $postCardUploadHandler            = new PostCardUploadHandler($postCardFileUrlParams);
                    $postCardSync                     = $postCardUploadHandler->postCardSync();

                    if ($postCardSync[$offer_id]) {
                        foreach ($orderIds as $orderId) {
                            if (Announcement::createForOrder($orderId, Announcement::ANNOUNCED)) {
                                new \history_note($orderId, get_current_user_id(), 'history-note-announcement-created', "Announcement has been sent.");
                            }
                            fileLogger::log_flow(__METHOD__." Announcement attribute(".Announcement::ANNOUNCED.") saved for order {$orderId}");
                        }
                    } else {
                        fileLogger::log_error('Unable to upload file for offerId - '.$offer_id, __METHOD__.' - Postcard csv file generation exception');
                    }
                }
            } else {
                fileLogger::log_flow(__METHOD__." No postcard data found to upload");
            }
        } catch (\Throwable $e) {
            fileLogger::log_error($e->getMessage(), __METHOD__.' - Postcard csv file generation exception');
        }
    }
}
