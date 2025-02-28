<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Illuminate\Contracts\Support\Arrayable;

interface ApiClientProvider extends Arrayable
{
    /**
     * Get the Guzzle response object for the request
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface;

    /**
     * Get the HTTP status code from the response
     *
     * @return int|null
     */
    public function getStatusCode(): ?int;

    /**
     * Get the parsed content from the response
     *
     * @return array|string
     */
    public function getContent();

    /**
     * Set whether or not we should return an array as the response
     * @param bool $returnArray
     * @return $this
     */
    public function setReturnArray(bool $returnArray): self;

    /**
     * @return \Throwable|null
     */
    public function getError(): ?\Throwable;

    /**
     * Merge in the default GuzzleHttp options for this request
     *
     * @param array $options An array of GuzzleHttp options to merge with the defaults
     * @return array
     */
    public function mergeDefaultOptions(array $options): array;

    /**
     * Get the API url by appending the endpoint to the base API URL
     * @param string|null $url
     * @return string
     */
    public function getApiUrl(?string $url = null): string;

    /**
     * @param string|null $api_url
     * @return self
     */
    public function setApiUrl(?string $api_url): self;

    /**
     * @return array
     */
    public function getOptions(): array;
    /**
     * @param array $options
     * @return ApiClientProvider
     */
    public function setOptions(array $options): ApiClientProvider;

    /**
     * @return array
     */
    public function getExtraData(): array;

    /**
     * @param array $extraData
     * @return ApiClientProvider
     */
    public function setExtraData(array $extraData): ApiClientProvider;

    /**
     * Add tls version option to the given array of GuzzleHttp CURL options
     *
     * @param int $version The version value to set
     * @return self
     */
    public function addTlsOption(int $version = CURL_SSLVERSION_TLSv1_2): self;

    /**
     * Add the provided curl option to the given array of GuzzleHttp CURL options
     *
     * @param string $curlOption GuzzleHttp option name
     * @param mixed $value       The value to set for the option
     * @return self
     */
    public function addCurlOption(string $curlOption, $value): self;

    /**
     * Add the provided header to the given array of GuzzleHttp CURL options
     *
     * @param string $header Http header name
     * @param mixed  $value  The value to set for the header
     * @return self
     */
    public function addHeader(string $header, $value): self;

    /**
     * Add the key/value provided to the array of GuzzleHttp CURL params
     *
     * @param string $key   The key to set
     * @param mixed  $value The value to set
     * @return self
     */
    public function addData(string $key, $value): self;

    /**
     * Add accepts JSON header to the given array of GuzzleHttp CURL options
     * @param []  $options The existing array of GuzzleHttp options
     * @return self
     */
    public function addJsonHeader(): self;

    /**
     * Add accepts JSON header to the given array of GuzzleHttp CURL options
     * @param []  $options The existing array of GuzzleHttp options
     * @return self
     */
    public function addFormEncodedHeader(): self;

    /**
     * Add the provided key to the given array if it doesnt already exist
     *
     * @param string  $key          The name of the key to add
     * @param mixed   $defaultValue The default value to set for the key
     */
    public function addKey(string $key, $defaultValue = []): void;

    /**
     * Send a get request to the given url with the provided options and data
     *
     * @param string|null $endpoint The API endpoint to send the request to, should not include query string
     * @param array  $data     The GET or POST data to send with the request
     * @param array  $options  The GuzzleHttp options to send with the request
     * @return string|array
     */
    public function get(?string $endpoint = null, $data = [], $options = []);

    /**
     * Send a post request to the given url with the provided options and data
     *
     * @param string|null $endpoint The API endpoint to send the request to, should not include query string
     * @param array  $data     The GET or POST data to send with the request
     * @param array  $options  The GuzzleHttp options to send with the request
     * @return string|array
     */
    public function post(?string $endpoint = null, $data = [], $options = []);

    /**
     * Send a request of the given type to the given url with the provided options and data
     *
     * @param string $method   The HTTP method to use, GET or POST
     * @param string|null $url The url to send the request to, should not include query string
     * @param array  $data     The GET or POST data to send with the request
     * @param array  $options  The GuzzleHttp options to send with the request
     * @return string|array
     */
    public function send(string $method, ?string $url = null, array $data = [], array $options = []);

}