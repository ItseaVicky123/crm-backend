<?php

namespace App\Lib\Inventory;

use Illuminate\Support\Facades\File;
use App\Services\Providers\Provider;
use App\Models\Services\FtpService;
use App\Providers\External\Ftp\FtpFileProvider;
use App\Models\ProductInventory;
use App\Models\Warehouse;
use fileLogger;

/**
 * Class InventoryFileHandler
 * 1.   Get List of warehouse
 * 2.   Get all Inventory's by warehouse and Create CSV file for every warehouse
 * 3.   Send CSV in FTP
 *
 * @package App\Lib\Inventory
 */
class InventoryFileHandler
{
    protected array $csvData;

    protected array $csvHeadings;

    public function __construct()
    {
        // Initialize the data when create object of this class
        $this->init();
    }

    /**
     * Initialize the data
     *
     * @return void
     */
    private function init(): void
    {
        // #1. Set headers for csv file
        $this->setCsvHeaders();

        // #2. Set data from database to array for pass the csv create function
        $this->setFilteredDataForCSV();
    }

    /**
     * Set headers here for CSV file
     *
     * @return void
     */
    private function setCsvHeaders()
    {
        $this->csvHeadings = [
            'warehouse id',
            'SKU',
            'inventory quantity count'
        ];
    }

    /**
     * Get the data from database and create array for create csv
     *
     * @return void
     */
    private function setFilteredDataForCSV()
    {
        // Get All inventories
        $inventories = ProductInventory::whereIn('warehouse_id', function ($query) {
            $query->select('id')->from(with(new Warehouse)->getTable())->where('ftp_profile_id', '>', 0)->where('active', 1);
        })->get();

        // Create array for CSV using warehouse id as index
        foreach ($inventories as $key => $row) {
            $this->csvData[$row->warehouse_info['id']][] = [
                $row->warehouse_id,
                $row->sku,
                $row->quantity
            ];
        }
    }

    /**
     * Execute the process for upload file to sftp
     *
     * @return array $returnData
     */
    public function run(): array
    {
        $returnData = [];
        try {
            // Get the warehouse which has ftp_profile_id (merchant’s SFTP) set
            $warehouses = Warehouse::where('ftp_profile_id', '>', 0)->where('active', 1)->get();

            foreach ($warehouses as $warehouse) {
                // Create a CSV for a warehouse and Upload to FTP profile which set on warehouse
                $returnData[$warehouse->id] = $this->createAndSendCSVForWarehouse($warehouse);
            }
        } catch (\Exception $e) {
            fileLogger::log_error(__METHOD__." Generic Exception caught: {$e->getMessage()}");
        }
        return $returnData;
    }

    /**
     * Create Inventory csv file for a warehouse and send to merchant’s SFTP
     * @param $warehouse
     * @return bool
     */
    private function createAndSendCSVForWarehouse($warehouse): bool
    {
        $returnData = false;
        try {
            if (! $warehouse->id) {
                throw new \Exception("Invalid warehouse.");
            }

            if (! isset($this->csvData[$warehouse->id])) {
                throw new \Exception("No data available for this warehouse ({$warehouse->id}).");
            }

            // Path where temp CSV file will be created For a warehouse
            $filePath = storage_path('logs/').sprintf('Inventory-for-%s-%s.csv', $warehouse->id, date('Y-m-d-H-i-s'));

            // Create a temp Inventory CSV for a warehouse which will be push on merchant’s SFTP
            $filePath = $this->createArrayToCsv($this->csvHeadings, $this->csvData[$warehouse->id], $filePath);

            if (! $filePath) {
                throw new \Exception("Inventory CSV file creation failed from warehouse: {$warehouse->id}");
            }

            // Send the CSV file to merchant’s SFTP
            if ($this->sendToSFTP($warehouse->ftp_profile_id, $filePath)) {
                $returnData = true;
            } else {
                fileLogger::log_flow(__METHOD__." - Inventory CSV file uploading failed from temp path: {$filePath} profile id : {$warehouse->ftp_profile_id}");
            }

            // Delete the temp csv file
            if (File::delete($filePath)) {
                fileLogger::log_flow(__METHOD__." - Inventory CSV file deleted from temp path: {$filePath} profile id : {$warehouse->ftp_profile_id}");
            } else {
                fileLogger::log_flow(__METHOD__." - Inventory CSV file delete failed: {$filePath} profile id : {$warehouse->ftp_profile_id}");
            }
        } catch (\Exception $e) {
            fileLogger::log_error(__METHOD__." - Generic Exception caught: {$e->getMessage()}");
        }

        return $returnData;
    }

    /**
     * Posting CSV file to the merchant’s SFTP
     *
     * @param int $profile_id
     * @param string $filePath
     * @return bool $returnData
     */
    private function sendToSFTP(int $profile_id, string $filePath): bool
    {
        $returnData = false;
        try {
            if (! $profile_id) {
                throw new \Exception("Provider Profile id is required.");
            }

            if (! $filePath) {
                throw new \Exception("CSV File path is required.");
            }

            // Creating Provider instance
            $providerInstance = Provider::getProviderServiceInstance(FtpFileProvider::ACCOUNT_ID, FtpService::PROVIDER_TYPE);

            if (! $providerInstance) {
                throw new \Exception("Failed to load provider instance for provider type {FtpFileProvider::ACCOUNT_ID}, account {FtpFileProvider::ACCOUNT_ID}.");
            }

            // Get the object
            $providerProfile = FtpService::find($profile_id);

            if (! $providerProfile) {
                throw new \Exception("Failed to load provider profile instance for provider type {FtpFileProvider::ACCOUNT_ID}, account {FtpFileProvider::ACCOUNT_ID}.");
            }

            $providerInstance->setService($providerProfile);

            if ($providerInstance->uploadAsFile($filePath, $providerInstance->getUploadPath()."/".basename($filePath))) {
                $returnData = true;
            } else {
                throw new \Exception(" Inventory file uploading filed path( {$filePath} )");
            }
        } catch (\Exception $e) {
            fileLogger::log_error(__METHOD__." - Generic Exception caught: {$e->getMessage()}");
        }

        return $returnData;
    }

    /**
     * Generate CSV form array data
     *
     * @param array $headings
     * @param array $data
     * @param string $filePath
     *
     * @return bool|string $returnData
     */
    private function createArrayToCsv(array $headings, array $data, string $filePath)
    {
        $returnData = false;
        try {
            if (empty($data)) {
                throw new \Exception("Empty data array provided for create csv file.");
            }

            if (empty($headings)) {
                throw new \Exception("Empty headings array provided for create csv file.");
            }

            if (! $filePath) {
                throw new \Exception("Empty file path provided for create csv file.");
            }

            $handle = fopen($filePath, 'w');
            fputcsv($handle, $headings);

            foreach ($data as $key => $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);

            fileLogger::log_flow(__METHOD__." - CSV file generated in path : {$filePath}.");

            $returnData = $filePath;
        } catch (\Throwable $e) {
            fileLogger::log_error($e->getMessage(), __METHOD__.' - CSV file generation exception for path : {$filePath}.');
        }

        return $returnData;
    }
}
