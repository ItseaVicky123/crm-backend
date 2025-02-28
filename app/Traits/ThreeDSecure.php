<?php

namespace App\Traits;

use Illuminate\Http\Request;
use function get_server_info;

/**
 * Trait ThreeDSecure
 * @package App\Traits
 */
trait ThreeDSecure
{
    /**
     * @var string|null
     */
    protected ?string $acsUrl = '';

    /**
     * @var bool|null
     */
    protected ?bool $is3DSecure;

    /**
     * @var string|null
     */
    protected ?string $returnUrl = '';

    /**
     * @var int|null
     */
    protected ?int $threeDSVersion;

    /**
     * @param $acsUrl
     * @return $this
     */
    public function setAcsUrl($acsUrl)
    {
        $this->acsUrl = $acsUrl;
        return $this;
    }

    /**
     * @param $orderId
     * @return $this
     */
    public function setReturnUrl($orderId)
    {
        $this->returnUrl = get_server_info('site_server') . "/admin/alternative_provider/ajax.php?mode=3d_capture&ll_token=3ds_process&id={$orderId}";
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param $type
     * @return $this
     */
    public function setThreeDSessionKey($key, $value, $type)
    {
        putSession("three_d_secure.{$key}.type", $type);
        putSession("three_d_secure.{$key}.value", $value);
        return $this;
    }
}
