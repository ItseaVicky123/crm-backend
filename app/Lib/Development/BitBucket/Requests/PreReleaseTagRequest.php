<?php

namespace App\Lib\Development\BitBucket\Requests;

class PreReleaseTagRequest extends TagRequest
{
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
}
