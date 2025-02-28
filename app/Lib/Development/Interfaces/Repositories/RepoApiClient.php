<?php

namespace App\Lib\Development\Interfaces\Repositories;

interface RepoApiClient
{
    /**
     * @param string|null $filter
     * @param string|null $sort
     * @param int|null    $pageLength
     * @param int|null    $page
     * @param string|null $endpoint
     * @return RepoApiResponse
     */
    public function getTags(?string $filter = null, ?string $sort = null, ?int $pageLength = null, ?int $page = null, ?string $endpoint = null): RepoApiResponse;

    /**
     * @param string $newTagName
     * @param string $mergeCommit
     * @return RepoApiResponse
     */
    public function pushTag(string $newTagName, string $mergeCommit): RepoApiResponse;

    /**
     * @param string|null $repo
     * @return self
     */
    public function setRepo(?string $repo): self;
}
