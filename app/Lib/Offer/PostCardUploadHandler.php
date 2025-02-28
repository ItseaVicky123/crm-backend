<?php

namespace App\Lib\Offer;

use App\Services\Providers\Provider;
use App\Models\Services\FtpService;
use Illuminate\Support\Facades\Log;
use App\Providers\External\Ftp\FtpFileProvider;
use App\Models\Offer\OfferLink;
use Illuminate\Support\Facades\File;
use crm_notification;
use fileLogger;

/**
 * Main handler for postcard csv upload to sftp
 * Class PostCardUploadHandler
 *
 * @package App\Lib\Offer
 */
class PostCardUploadHandler
{
    /**
     * @var array|null $postCardFiles
     */
    protected ?array $postCardFiles = null;

    /**
     * @var int|null $providerTypeId
     */
    protected ?int $providerTypeId = null;

    /**
     * PostCardUploadHandler constructor.
     *
     * @param array $param
     * @throws \Exception
     */
    public function __construct(array $param)
    {
        if (empty($param)) {
            fileLogger::log_error(__METHOD__." PostCardUploadHandler: Array of Offer id and upload path is required.");
            throw new \Exception("Array of Offer id and upload path is required.", __CLASS__, __METHOD__);
        }
        $this->postCardFiles  = $param;
        $this->providerTypeId = FtpService::PROVIDER_TYPE;
    }

    public function postCardSync(): array
    {
        $return = [];
        foreach ($this->postCardFiles as $offerId => $filePath) {
            if($offerLink = OfferLink::where('offer_id', $offerId)->first()) {
                if($this->syncFileToFTP($offerLink->profile_id, $filePath)){
                    $return[$offerId] = true;
                }else{
                    $return[$offerId] = false;
                }
            }else{
                $return[$offerId] = false;
                fileLogger::log_error(__METHOD__." PostCardUploadHandler: Invalid offer id (". $offerId.") provided.");
            }
        }
        return $return;
    }

    /**
     * Perform the postcard csv upload operation.
     *
     * @return void
     * @throws \Exception
     */
    public function syncFileToFTP($profile_id, $filePath): bool
    {
        $return = false;
        try {
            if ($providerInstance = Provider::getProviderServiceInstance(FtpFileProvider::ACCOUNT_ID, $this->providerTypeId)) {
                if ($providerProfile = FtpService::find($profile_id)) {
                    $providerInstance->setService($providerProfile);
                    if ($providerInstance->uploadAsFile($filePath, $providerInstance->getUploadPath()."/".basename($filePath))) {
                        $return   = true;
                        $notifier = new crm_notification();
                        $tokens = [
                            '{file_name}'    => basename($filePath)
                        ];
                        $notifier->send_system_notification(false, crm_notification::CONFIG_POSTCARD_FILE_DROP, $tokens);

                        if (File::delete($filePath)) {
                            Log::debug(__METHOD__." Postcard file deleted from local path: {$filePath} profile id : {$profile_id}");
                        } else {
                            Log::debug(__METHOD__." Postcard file deleted failed: {$filePath} profile id : {$profile_id}");
                        }
                    } else {
                        throw new \Exception(" Postcard file uploading filed path( {$filePath} )");
                    }
                } else {
                    throw new \Exception("Failed to load provider profile instance for provider type {$this->providerTypeId}, account {FtpFileProvider::ACCOUNT_ID}.");
                }
            } else {
                throw new \Exception("Failed to load provider instance for provider type {$this->providerTypeId}, account {FtpFileProvider::ACCOUNT_ID}.");
            }
        } catch (\Exception $e) {
            fileLogger::log_error(__METHOD__." Generic Exception caught: {$e->getMessage()}");
        }

        return $return;
    }
}
