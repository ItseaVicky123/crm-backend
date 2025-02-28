<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait PaymentProviderResponse
 * @package App\Traits
 */
trait PaymentProviderResponse
{
    /**
     * @var string
     */
    protected $authorizationCode = '';

    /**
     * @var string
     */
    protected $errorMessage = '';

    /**
     * @var bool
     */
    protected $setPending = false;

    /**
     * @var string
     */
    protected $transactionId = '';

    /**
     * @param null $extra
     * @param bool $logApiCall
     * @return array
     */
    protected function successResponse($extra = null, $logApiCall = true)
    {
        $response = [
            0              => '1',
            1              => 'resp_code',
            3              => '',
            4              => $this->authorizationCode,
            6              => $this->transactionId,
            'authId'       => $this->authorizationCode,
            'errorMessage' => '',
            'resp_code'    => '000',
            'status'       => 'OK',
            'transId'      => $this->transactionId,
        ];

        if ($this->setPending) {
           $response['pending'] = $this->setPending;
        }

        if ($extra) {
           $response = array_replace($response, $extra);
        }

        if ($logApiCall) {
            $this->logResponse($response);
        }

        return $response;
    }

    /**
     * @param null $extra
     * @param bool $logApiCall
     * @return array
     */
    protected function failureResponse($extra = null, $logApiCall = true)
    {
        $response = [
            0              => '3',
            3              => $this->errorMessage,
            4              => '',
            'resp_code'    => '999999',
            'authId'       => '',
            'transId'      => 0,
            'errorMessage' => $this->errorMessage,
            'status'       => 'ERROR',
        ];

        if ($extra) {
            $response = array_replace($response, $extra);
        }

        if ($logApiCall) {
            $this->logResponse($response);
        }

        return $response;
    }

    /**
     * @param null $extra
     * @return array
     */
    protected function unknownResponse($extra = null)
    {
        $response = [
            0              => '3',
            3              => 'invalid response data',
            4              => '',
            'resp_code'    => '999999',
            'authId'       => '',
            'transId'      => 0,
            'errorMessage' => 'invalid response data',
            'status'       => 'ERROR',
        ];

        if ($extra) {
            $response = array_replace($response, $extra);
        }

        $this->logResponse($response);

        return $response;
    }

    /**
     * @param $response
     * @return $this
     */
    protected function logResponse($response)
    {
        Log::debug(class_basename($this) . ' Payment Provider Response:', [
            'response' => $response,
        ]);

        return $this;
    }
}
