<?php

namespace App\Lib\Development\BitBucket\Requests;

class BitBucketRequest
{
    /**
     * The bitbucket api endpoint to hit
     * @see https://developer.atlassian.com/bitbucket/api/2/reference/resource/
     * @var string|null
     */
    protected ?string $endpoint = null;

    /**
     * Bitbucket syntax query/filter string
     * @see https://developer.atlassian.com/bitbucket/api/2/reference/meta/filtering
     * @var string|null
     */
    protected ?string $filter = null;

    /**
     * Bitbucket syntax sort string
     * @see https://developer.atlassian.com/bitbucket/api/2/reference/meta/filtering
     * @var string|null
     */
    protected ?string $sort = null;

    /**
     * Number of results per page (supports 10 - 100)
     * @var int|null
     */
    protected ?int $pageLength = 10;

    /**
     * The page number of the paginated results to return
     * @var int|null
     */
    protected ?int $page = 1;

    /**
     * True if we should iterate over all available pages and return the aggregated results
     * @var bool
     */
    protected bool $fetchAll = false;

    /**
     * @var string
     */
    protected string $apiMethod = 'GET';

    /**
     * @var array
     */
    protected array $postData = [];

    /**
     * BitBucketRequest constructor.
     *
     * @param string      $apiMethod
     * @param string|null $filter
     * @param string|null $sort
     * @param int|null    $pageLength
     * @param int|null    $page
     * @param string|null $endpoint
     */
    public function __construct(
        string $apiMethod  = 'GET',
        ?string $filter     = null,
        ?string $sort       = null,
        ?int    $pageLength = null,
        ?int    $page       = null,
        ?string $endpoint   = null
    )
    {
        $this->apiMethod  = $apiMethod  ?? $this->apiMethod;
        $this->filter     = $filter     ?? $this->filter;
        $this->sort       = $sort       ?? $this->sort;
        $this->pageLength = $pageLength ?? $this->pageLength ?? config('bitbucket.defaults.pageLength');
        $this->page       = $page       ?? $this->page;
        $this->endpoint   = $endpoint   ?? $this->endpoint;
    }

    /**
     * @param array $postData
     * @return BitBucketRequest
     */
    public function setPostData(array $postData): BitBucketRequest
    {
        $this->postData = $postData;

        return $this;
    }

    /**
     * @param string|null $endpoint
     * @return BitBucketRequest
     */
    public function setEndpoint(?string $endpoint): BitBucketRequest
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @param string|null $filter
     * @return BitBucketRequest
     */
    public function setFilter(?string $filter): BitBucketRequest
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @param string|null $sort
     * @return BitBucketRequest
     */
    public function setSort(?string $sort): BitBucketRequest
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @param string|null $pageLength
     * @return BitBucketRequest
     */
    public function setPageLength(?string $pageLength): BitBucketRequest
    {
        $this->pageLength = $pageLength;

        return $this;
    }

    /**
     * @param int|null $page
     * @return BitBucketRequest
     */
    public function setPage(?int $page): BitBucketRequest
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param bool $fetchAll
     * @return BitBucketRequest
     */
    public function setFetchAll(bool $fetchAll): BitBucketRequest
    {
        $this->fetchAll = $fetchAll;

        return $this;
    }

    /**
     * @param string $apiMethod
     * @return BitBucketRequest
     */
    public function setApiMethod(string $apiMethod): BitBucketRequest
    {
        $this->apiMethod = $apiMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiMethod(): string
    {
        return $this->apiMethod;
    }

    /**
     * @param string $baseUrl
     * @param string $api
     * @param string $org
     * @param string $repo
     * @return string
     */
    public function getApiUrl(string $baseUrl, string $api, string $org, string $repo): string
    {
        $baseUrl  = rtrim($baseUrl, '/');
        $api      = rtrim(ltrim($api, '/'), '/');
        $org      = rtrim(ltrim($org, '/'), '/');
        $repo     = rtrim(ltrim($repo, '/'), '/');
        $endpoint = rtrim(ltrim($this->endpoint, '/'), '/');
        return "{$baseUrl}/{$api}/{$org}/{$repo}/{$endpoint}";
    }

    /**
     * @return array
     */
    public function getApiParams(): array
    {
        if ($this->apiMethod === 'GET') {
            $data = [
                'q'       => $this->filter,
                'sort'    => $this->sort,
                'page'    => $this->page,
                'pagelen' => $this->pageLength,
            ];
        } else {
            $data = $this->postData;
        }
        return $data;
    }
}
