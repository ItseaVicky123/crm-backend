<?php

namespace App\Lib\Payments\Nmi\Interfaces;

interface NmiResponseProvider extends NmiTransactionTypeProvider
{
    /**
     * Get the human readable text for the given response code
     * @param string|int $responseCode
     * @return string
     */
    public  function getResponseCodeText($responseCode): string;

    /**
     * Get the human readable text for the given CVV response code
     * @param string|int $responseCode
     * @return string
     */
    public  function getCvvResponseCodeText($responseCode): string;

    /**
     * Get the human readable text for the given AVS response code
     * @param string|int $responseCode
     * @return string
     */
    public  function getAvsResponseCodeText($responseCode): string;

    /**
     * Check if the given AVS response code indicates an exact AVS match
     * @param string|int $responseCode
     * @return string
     */
    public  function isExactAvsMatch($responseCode): string;

    /**
     * Check if the given AVS response code indicates an partial AVS match
     * @param string|int $responseCode
     * @return string
     */
    public  function isPartialAvsMatch($responseCode): string;

    /**
     * Check if the given AVS response code indicates AVS is unavailable for the cc used
     * @param string|int $responseCode
     * @return string
     */
    public  function isAvsUnavailable($responseCode): string;


    /**
     * Check if the given CVV response code indicates CVV is unavailable for the cc used
     * @param string|int $responseCode
     * @return string
     */
    public  function isCvvUnavailable($responseCode): string;

    /**
     * Check if the given CVV response code indicates a CVV match
     * @param string|int $responseCode
     * @return string
     */
    public  function isCvvMatch($responseCode): string;

    /**
     * @return string|null
     */
    public  function getRawResponse(): ?string;

    /**
     * @return int|null
     */
    public  function getResponse(): ?int;

    /**
     * @return string|null
     */
    public  function getResponseText(): ?string;

    /**
     * @return string|null
     */
    public  function getAuthcode(): ?string;

    /**
     * @return string|null
     */
    public  function getTransactionid(): ?string;

    /**
     * @return string|null
     */
    public  function getAvsresponse(): ?string;

    /**
     * @return string|null
     */
    public  function getCvvresponse(): ?string;

    /**
     * @return string|null
     */
    public  function getOrderid(): ?string;

    /**
     * @return int|null
     */
    public  function getResponseCode(): ?int;

    /**
     * @return int|null
     */
    public  function getEmvAuthResponseData(): ?int;

    /**
     * @return string|null
     */
    public  function getCustomerVaultId(): ?string;

    /**
     * @return string|null
     */
    public  function getCcNumber(): ?string;

    /**
     * @return string|null
     */
    public  function getCheckaba(): ?string;

    /**
     * @return string|null
     */
    public  function getCheckaccount(): ?string;

    /**
     * @return string|null
     */
    public  function getTransactionType(): ?string;

    /**
     * @return bool|null
     */
    public  function getAvsExactMatch(): ?bool;

    /**
     * @return bool|null
     */
    public  function getAvsUnavailable(): ?bool;

    /**
     * @return bool|null
     */
    public  function getCvvMatch(): ?bool;

    /**
     * @return bool|null
     */
    public  function getCvvUnavailable(): ?bool;

    /**
     * @return bool|null
     */
    public  function getAvsPartialMatch(): ?bool;

    /**
     * @return string|null
     */
    public  function getAvsMessage(): ?string;

    /**
     * @return string|null
     */
    public  function getCvvMessage(): ?string;

    /**
     * @return string|null
     */
    public  function getTransactionMessage(): ?string;

    /**
     * @return string|null
     */
    public  function getProcessorId(): ?string;

    /**
     * @return bool|null
     */
    public  function wasSuccessful(): ?bool;

    /**
     * Parse the NMI API response and fill this object's properties from the returned data
     * @param string $rawResponse
     * @return self
     */
    public function parseResponse(string $rawResponse): self;

    /**
     * Set the current object's properties from an array;
     * @param array $data
     * @return self
     */
    public function fromArray(array $data = []): self;
}