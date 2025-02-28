<?php

namespace App\Lib\Development\BitBucket;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use \Throwable;
use Psr\Http\Message\ResponseInterface;
use App\Lib\Development\Interfaces\Repositories\RepoApiResponse;

class BitBucketResponse implements RepoApiResponse
{
    public const SUCCESSFUL_CODE = 200;

    protected ?ResponseInterface $rawResponse = null;

    protected ?int $statusCode = null;

    protected ?array $content = null;

    /**
     * The error if one was thrown
     * @var ?Throwable
     */
    protected ?Throwable $error = null;

    /**
     * BitBucketResponse constructor.
     *
     * @param ?ResponseInterface $rawResponse
     */
    public function __construct(?ResponseInterface $rawResponse = null)
    {
        $this->parseResponse($rawResponse);

    }

    /**
     * @return array|\Psr\Http\Message\ResponseInterface|null
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @param array|\Psr\Http\Message\ResponseInterface|null $rawResponse
     * @return BitBucketResponse
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     * @return BitBucketResponse
     */
    public function setStatusCode(?int $statusCode): BitBucketResponse
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getContent(): ?array
    {
        return $this->content;
    }

    /**
     * @param array|null $content
     * @return BitBucketResponse
     */
    public function setContent(?array $content): BitBucketResponse
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Throwable|null
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @param Throwable $error
     * @return BitBucketResponse
     */
    public function setError(Throwable $error): BitBucketResponse
    {
        $this->error = $error;
        Log::debug('Tagify: '.__METHOD__.' failed to push new tag', [
            'bitBucketError' => $error->getMessage(),
        ]);
        return $this;
    }

    /**
     * @param ResponseInterface|null $rawResponse
     */
    public function parseResponse(?ResponseInterface $rawResponse = null): void
    {
        if ($rawResponse instanceof ResponseInterface) {
            $this->rawResponse = $rawResponse;
            $this->statusCode  = $rawResponse->getStatusCode();
            $content           = (string)$rawResponse->getBody();
            $this->content     = json_decode($content, true);
        }
    }

    /**
     * Get the data returned by the api call
     * @return Collection
     */
    public function get(): Collection
    {
        return collect(Arr::get($this->content, 'values', []));
    }

    /**
     * Check if the related api call was successful
     * @return bool
     */
    public function successful(): bool
    {
        return strpos($this->statusCode, '2', 0)  === 0;
    }
}
