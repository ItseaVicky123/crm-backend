<?php


namespace App\Lib\Utilities;


use App\Lib\Utilities\ImportType\BaseTypeHelper;
use App\Models\User;
use crm_notification;
use current_domain;
use downloader;
use Illuminate\Http\UploadedFile;
use App\Models\OrderBatch;
use Illuminate\Support\Facades\Log;
use product\bundle\resource;
use system_security;


/**
 * Class ImportExport
 * @package App\Lib\Utilities
 */
class ImportExport
{
    protected const STATUS_IN_PROCESS  = 1;
    protected const STATUS_COMPLETE    = 2;
    protected const STATUS_COMPLETE_NR = 3;
    protected const STATUS_ERROR       = 4;
    protected const STATUS_RECOVERY    = 5;

    protected const CSV_LENGTH = 5000;

    /**
     * @var UploadedFile|null
     */
    private ?UploadedFile $importFile = null;

    /**
     * @var string|null
     */
    private ?string $importType = null;

    /**
     * @var string|null
     */
    private ?string $errorMessage = null;

    /**
     * @var OrderBatch
     */
    private OrderBatch $orderBatch;

    /**
     * @var int
     */
    private int $rowCount = 0;

    /**
     * @var false|resource
     */
    private $inputFile;

    /**
     * @var BaseTypeHelper
     */
    public BaseTypeHelper $typeHelper;

    /**
     * @var int
     */
    private int $rowsProcessed;

    /**
     * @var array
     */
    public array $record = [];

    /**
     * @var mixed
     */
    private $inputHeaders = [];

    /**
     * @var bool
     */
    private bool $recoveryMode = false;

    /**
     * @var resource|bool
     */
    private $outputFile;

    /**
     * @var mixed
     */
    private $currentRownum;

    /**
     * @var int
     */
    private int $successCount = 0;

    /**
     * @var int
     */
    private int $failedCount = 0;

    /**
     * @var bool
     */
    private bool $processImmediately;

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @param UploadedFile|null $file
     * @param string $type
     * @return OrderBatch|null
     */
    public function handleFileUpload(?UploadedFile $file, string $type): ?OrderBatch
    {
        $return           = null;
        $this->importFile = $file;

        if ($type && $this->importFile) {
            $this->importType = $type;
            $cname = "App\\Lib\\Utilities\\ImportType\\" . ucwords($this->importType) . 'Helper';

            if (class_exists($cname)) {
                $this->typeHelper = new $cname;
                $return = $this->addToOrderBatch();
            } else {
                $this->errorMessage = __FUNCTION__ . " - Could not load the helper class for this import type - class does not exist for {$this->importType}.";
            }
        } else if (!$this->importFile) {
            $this->errorMessage = __FUNCTION__ . " - Could not load file for import - unknown error occurred.";
        }

        return $return;
    }

    /**
     * @return OrderBatch|null
     */
    protected function addToOrderBatch(): ?OrderBatch
    {
        $return = null;

        if ($this->importType) {
            $fileExtension = $this->importFile->getClientOriginalExtension();

            if (in_array($fileExtension, $this->typeHelper->validExtensions, true)) {
                ini_set('auto_detect_line_endings', true);
                $originalFileName = basename($this->importFile->getClientOriginalName());
                $tmpName          = $this->importFile->getRealPath();
                $user             = User::findOrFail($_SESSION['admin_id']);
                $ordersBatch      = OrderBatch::create([
                    'importType'    => 'import',
                    'importSubType' => ucfirst($this->importType) . ' Import',
                    'name'          => $user->username,
                    'email'         => $user->email,
                    'admin_id'      => current_user(),
                    'inputFileName' => $originalFileName,
                    'inputFile'     => $tmpName,
                    'cloud_flag'    => (int) (defined('CLOUD_ENV') && CLOUD_ENV),
                ]);

                if ($ordersBatch->ordersBatchId && $tmpName) {
                    $hash     = MD5($ordersBatch->ordersBatchId . $ordersBatch->createdOn . $tmpName . $ordersBatch->email);
                    $dir      = downloader::get_dir('imports');
                    $filename = "{$hash}.csv";
                    $fullPath = $dir . $filename;

                    if (!empty($hash) && file_exists($tmpName)) {
                        if (!file_exists($fullPath)) {
                            if ($this->importFile->move($dir, $filename)) {
                                if (system_security::encrypt_file($fullPath)) {
                                    $ordersBatch->update([
                                        'saved_file_hash' => $hash,
                                        'saved_file_path' => $fullPath,
                                    ]);
                                    $return = $ordersBatch->fresh();
                                } else {
                                    unlink($fullPath);
                                    $this->errorMessage = __FUNCTION__ . " - Failed to encrypt uploaded file! Path = {$fullPath}";
                                    Log::error($this->errorMessage);
                                }
                            } else {
                                $this->errorMessage = __FUNCTION__ . " - Failed to move uploaded file! Paths = {$tmpName} => {$fullPath}";
                                Log::error($this->errorMessage);
                            }
                        } else {
                            $this->errorMessage = __FUNCTION__ . " - Failed to save uploaded import file because destination already exists! Path = {$fullPath}";
                            Log::error($this->errorMessage);
                        }
                    } else {
                        $this->errorMessage = __FUNCTION__ . " - Failed to get file hash or file failed to upload! Hash = {$hash} | Temp File = {$tmpName}";
                        Log::error($this->errorMessage);
                    }
                }
            } else {
                $this->errorMessage = __FUNCTION__ . " - Filetype is not an acceptable format - Extension: {$fileExtension}}";
                Log::error($this->errorMessage);
            }
        }

        return $return;
    }

    /**
     * Check the import size vs import threshold
     *
     * @param OrderBatch $orderBatch
     * @return array $valid
     */
    public function processImportFile(OrderBatch $orderBatch): array
    {
        $valid            = false;
        $response         = [];
        $this->orderBatch = $orderBatch;
        if ($this->validateHeaders()) {
            if ($this->rowCount <= $this->typeHelper->threshold) {
                $this->processImmediately = true;
                $valid = $this->processImport();
            } else if ($this->rowCount > $this->typeHelper->maxThreshold) {
                $this->errorMessage = __FUNCTION__ . " - File size exceeded the maximum allowable size - max: {$this->typeHelper->maxThreshold}";
            }
        }
        if ($valid) {
            if ($this->rowsProcessed === $this->rowCount) {
                $endTime = date('Y-m-d H:i:s');
                $this->orderBatch->update([
                    'rows_to_process' => $this->rowCount,
                    'rows_processed'  => $this->rowsProcessed,
                    'completedOn'     => $endTime,
                ]);
            }
            $data = $this->orderBatch->fresh();
            $response = [
                'data' => $data,
            ];
        }

        return $response;
    }

    /**
     * Validate the expected headers
     *
     * @returns bool $valid
     */
    protected function validateHeaders(): bool
    {
        $valid = $this->loadFile();

        if ($valid && count($this->inputHeaders)) {
            array_walk($this->inputHeaders, [$this, 'translate_headers']);
            foreach ($this->typeHelper->columnMap as $expectedColumn) {
                $expectedColumn = strtolower($expectedColumn);
                if (!in_array($expectedColumn, $this->typeHelper->optionalColumns, true) && !in_array($expectedColumn, $this->inputHeaders, true)) {
                    if (!in_array($this->typeHelper->columnAliases[$expectedColumn], $this->inputHeaders, true)) {
                        $valid = false;
                        $pieces = ['<EXPECTED>' => $expectedColumn];
                        $this->errorMessage = __FUNCTION__ . " - invalid-headers - Header Column: {$pieces}";
                        Log::debug(__FUNCTION__ . " - failed to find {$expectedColumn} and {$this->typeHelper->columnAliases[$expectedColumn]} in expected columns");
                        break;
                    }
                    Log::debug(__FUNCTION__ . " - Legacy column alias being used. Found '{$this->typeHelper->columnAliases[$expectedColumn]}'. Use '{$expectedColumn}' instead");
                }
            }
        }

        return $valid;
    }

    /**
     * Load data from uploaded file
     *
     * @returns bool $valid
     */
    protected function loadFile(): bool
    {
        $valid = false;
        // Decrypt file
        if (system_security::decrypt_file($this->orderBatch->saved_file_path)) {

            if ($handle = fopen($this->orderBatch->saved_file_path, 'rb')) {
                $this->inputFile = &$handle;
                while (!feof($this->inputFile)) {
                    if (fgets($this->inputFile)) {
                        $this->rowCount++;
                    }
                }
                // Ignore header
                $this->rowCount--;

                if ($this->rowCount > 0) {
                    $valid = true;
                    // Reset pointer
                    rewind($this->inputFile);
                    // Extract headers
                    if ($fileData = fgetcsv($this->inputFile, 5000, ",")) {
                        $this->inputHeaders = $fileData ?: [];
                    }
                } else {
                    $this->errorMessage = "CSV File is empty.";
                }
            } else {
                $this->errorMessage = "Could not open file for reading, Invalid permissions.";
            }
        } else {
            $this->errorMessage = "Invalid permission, could not decrypt file.";
        }

        return $valid;
    }

    /**
     * Process the imported file
     *
     * @returns bool $valid
     */
    protected function processImport(): bool
    {
        $startTime = date('Y-m-d H:i:s');
        $this->orderBatch->update([
            'startedOn' => $startTime,
        ]);
        $valid = (bool) $this->inputFile;

        if ($valid) {
            register_shutdown_function([&$this, '_shutdown']);
            $outputFilename   = "batch{$this->orderBatch->ordersBatchId}_" . date('d_M_y_H_i_s') . '.csv';
            $this->outputFile = fopen(downloader::get_dir() . $outputFilename, 'ab');

            if (!$this->outputFile) {
                Log::error(__FUNCTION__ . " - failed to open output file {$outputFilename}");
            } else if (!$this->recoveryMode) {
                $this->orderBatch->update([
                    'outputFileName' => $outputFilename,
                ]);
            }

            if (!empty($this->outputFile) && !empty($this->inputFile)) {
                $this->addOutputHeaders();
                $this->orderBatch->update([
                    'inProgress' => self::STATUS_IN_PROCESS,
                ]);

                while ($rowData = $this->getNextRow()) {
                    $success             = false;
                    $this->rowsProcessed = $this->currentRownum;

                    if ($data = $this->typeHelper->validateImportRow($rowData)) {
                        $success = $this->saveRecord();
                    } else {
                        Log::error(__FUNCTION__ . " - row validation failed (row {$this->currentRownum})");
                        $this->appendOutputRow($this->typeHelper->lastRow);
                    }

                    if ($success) {
                        $this->successCount++;
                    } else {
                        $this->failedCount++;
                    }
                }

                fclose($this->outputFile);
                fclose($this->inputFile);

                $this->orderBatch->update([
                    'inProgress' => self::STATUS_COMPLETE
                ]);

                if (!$this->processImmediately) {
                    $this->sendBatchEmail();
                }
            } else {
                $type               = (empty($this->outputFile) ? 'output' : 'input');
                $this->errorMessage = __FUNCTION__ . " - Error with {$type} file";
                $valid              = false;
                Log::error($this->errorMessage);

            }
        } else {
            $this->orderBatch->update([
                'inProgress' => self::STATUS_ERROR,
            ]);
        }

        return $valid;
    }

    /**
     * Append row to CSV output file
     *
     * @param array $data
     */
    protected function appendOutputRow(array $data): void
    {
        if ($this->outputFile !== false) {
            fputcsv($this->outputFile, $data);
        }
    }

    /**
     * @return bool
     */
    protected function saveRecord(): bool
    {
        $success = $this->typeHelper->saveRecord();

        if ((bool) $this->typeHelper->lastRow) {
            if ($success) {
                $this->appendOutputRow($this->typeHelper->lastRow);
            } else {
                $this->typeHelper->lastRow['success'] = 0;
                $this->typeHelper->lastRow['status_message'] = "There was an error saving this {$this->importType} record";
                $this->appendOutputRow($this->typeHelper->lastRow);
            }
        }

        return $success;
    }

    /**
     * Pulls the next row from the input file
     *
     * @returns mixed $return
     */
    protected function getNextRow()
    {
        $return = false;

        if (!$this->inputFile) {
            $this->loadFile();
        }

        if ($data = fgetcsv($this->inputFile, self::CSV_LENGTH, ",")) {
            $return = $data;
            $this->currentRownum++;
        }

        return $return;
    }

    /**
     * Put headers in CSV output file
     */
    protected function addOutputHeaders(): void
    {
        if (!$this->recoveryMode) {
            $this->appendOutputRow($this->typeHelper->outputHeaders);
        }
    }

    /**
     * Send email to alert user that the batched file has been processed
     */
    protected function sendBatchEmail(): void
    {
        if ($this->orderBatch instanceof OrderBatch) {
            $notifier   = new crm_notification();
            $recipient  = $this->orderBatch->email;
            $full_name  = $this->orderBatch->name;
            $msg_date   = formatDateTimeNew($this->orderBatch->createdOn);
            $subject    = 'Import Complete';
            $brand      = current_domain::company_name();
            $email_body = "Your import request you submitted on {$msg_date} has been completed.<br/><br/>" . "This file is available now under Imported Results which can be accessible by logging into your {$brand} account and clicking on <strong>Admin Settings > Import/Export</strong>.";

            $notifier->send_notification($full_name, $recipient, $subject, $email_body);
        } else {
            Log::debug(__METHOD__ . " - Failed to load batch info for id {$this->orderBatch->ordersBatchId}");
        }
    }

    /**
     * Shutdown procedures to handle identification and marking of incomplete file processes
     */
    public function _shutdown(): void
    {
        if ($this->rowsProcessed < $this->rowCount) {
            $this->orderBatch->update([
                'rows_to_process' => $this->rowCount,
                'rows_processed' => $this->rowsProcessed,
            ]);
            $this->orderBatch->update([
                'inProgress' => self::STATUS_RECOVERY,
            ]);
        }

        if (!system_security::decrypt_file($this->orderBatch->saved_file_path)) {
            // Re-encrypt file
            system_security::encrypt_file($this->$this->orderBatch->saved_file_path);
        }
    }
}
