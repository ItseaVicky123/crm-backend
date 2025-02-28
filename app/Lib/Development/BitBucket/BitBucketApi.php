<?php

namespace App\Lib\Development\BitBucket;

use App\Lib\Development\BitBucket\Requests\TagRequest;
use GuzzleHttp\Client;
use App\Lib\Development\BitBucket\Requests\BitBucketRequest;
use App\Lib\Development\Interfaces\Repositories\RepoApiClient;
use App\Lib\Development\Interfaces\Repositories\RepoApiResponse;
use GuzzleHttp\RequestOptions;

class BitBucketApi implements RepoApiClient
{
    /**
     * Available API types
     */
    public const REPOSITORIES_API = 'repositories';
    public const ADDON_API        = 'addon';
    public const HOOKS_API        = 'hook_events';
    public const PR_API           = 'pullrequests';
    public const SNIPPETS_API     = 'snippets';
    public const TEAMS_API        = 'snippets';
    public const USER_API         = 'user';
    public const USERS_API        = 'users';
    public const WORKSPACES_API   = 'workspaces';

    protected string $baseUrl = 'https://api.bitbucket.org/2.0';

    /**
     * The name of the bitbucket api we'll be working with
     * @var string|null
     */
    protected ?string $api = null;

    /**
     * The name of the organization that owns the repo in bitbucket
     * @var string|null
     */
    protected ?string $org = null;

    /**
     * The name of the repo in bitbucket
     * @var string|null
     */
    protected ?string $repo = null;

    /**
     * @var Client|null
     */
    protected ?Client $client = null;

    /**
     * BitBucketApi constructor.
     *
     * @param string|null $username
     * @param string|null $password
     * @param string|null $org
     * @param string|null $repo
     */
    public function __construct(string $username = null, string $password= null, string $org = null, string $repo= null)
    {
        $this->setClient($username, $password, $org, $repo);
    }

    /**
     * @param string|null $username
     * @param string|null $password
     * @param string|null $org
     * @param string|null $repo
     * @param \GuzzleHttp\Client|null $client
     * @return $this
     */
    protected function setClient(string $username = null, string $password = null, string $org = null, string $repo= null, ?Client $client = null): self
    {
        if ($client) {
            $this->client = $client;
        } else {
            $username   = $username ?? (string)config('bitbucket.username');
            $password   = $password ?? (string)config('bitbucket.password');
            $this->org  = $org      ?? (string)config('bitbucket.defaults.organization');
            $this->repo = $repo     ?? (string)config('bitbucket.defaults.repo');
            if ($username && $password) {
                $this->client = new Client([
                    'auth' => [$username, $password],
                ]);
            }
        }
        return $this;
    }

    /**
     * @param string|null $api
     * @return self
     */
    public function setApi(?string $api): self
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @param string|null $org
     * @return self
     */
    public function setOrg(?string $org): self
    {
        $this->org = $org;

        return $this;
    }

    /**
     * @param string|null $repo
     * @return self
     */
    public function setRepo(?string $repo): self
    {
        $this->repo = $repo;

        return $this;
    }

    /**
     * @param string $baseUrl
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * @param string $newTagName
     * @param string $mergeCommit
     * @return RepoApiResponse
     */
    public function pushTag(string $newTagName, string $mergeCommit): RepoApiResponse
    {
        $request = new TagRequest();
        $request->setNewTagName($newTagName)
            ->setTargetHashName($mergeCommit)
            ->setApiMethod('POST');
        $this->setApi(self::REPOSITORIES_API);
        return $this->send($request);
    }

    /**
     * @param string|null $filter
     * @param string|null $sort
     * @param int|null $pageLength
     * @param int|null $page
     * @param string|null $endpoint
     * @return RepoApiResponse
     */
    public function getTags(?string $filter = null, ?string $sort = null, ?int $pageLength = null, ?int $page = null, ?string $endpoint = null): RepoApiResponse
    {
        $request = new TagRequest();
        if ($filter) {
            $request->setFilter($filter);
        }
        if ($sort) {
            $request->setSort($sort);
        }
        if ($pageLength) {
            $request->setPageLength($pageLength);
        }
        if ($page) {
            $request->setPage($page);
        }
        if ($endpoint) {
            $request->setEndpoint($endpoint);
        }

        $this->setApi(self::REPOSITORIES_API);
        return $this->send($request);
    }

    /**
     * @param BitBucketRequest $request
     * @return RepoApiResponse
     */
    public function send(BitBucketRequest $request): RepoApiResponse
    {
        $response = new BitBucketResponse();
        $url      = $request->getApiUrl($this->baseUrl, $this->api, $this->org, $this->repo);
        $method   = $request->getApiMethod();
        $params   = $request->getApiParams();
        try {
            $queryString = $method === 'GET' ? '?'.http_build_query($params) : '';
            $url.=         $queryString;
            $apiResponse = null;
            $options     = [];
            if ($params && $method !== 'GET') {
                $options = [RequestOptions::JSON => $params];
            }
            $rawResponse  = $this->client->request($method, $url, $options);
            $response->parseResponse($rawResponse);
        } catch (\Throwable $e) {
            $response->setError($e);
        }

        return $response;
    }
}


//https://api.bitbucket.org/2.0/repositories/limelightcrm/legacy/refs/tags?q=name ~ "2021."  OR name ~ "2020."&sort=-name&pagelen=100
//https://api.bitbucket.org/2.0/repositories/limelightcrm/legacy/refs/tags?q=name+%7E+%222021.%22++OR+name+%7E+%222020.%22&sort=-name&page=1&pagelen=100
