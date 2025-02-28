<?php

namespace App\Lib\Development\BitBucket\Requests;

class TagRequest extends BitBucketRequest
{
    /**
     * The bitbucket api endpoint to hit
     * @see https://developer.atlassian.com/bitbucket/api/2/reference/resource/
     * @var string|null
     */
    protected ?string $endpoint = '/refs/tags';

    /**
     * Number of results per page (supports 10 - 100)
     * @var int|null
     */
    protected ?int $pageLength = 100;

    /**
     * True if we should iterate over all available pages and return the aggregated results
     * @var bool
     * @TODO Later Implement fetch all feature to loop over responses till all paginated results are aggregated
     */
    protected bool $fetchAll = true;

    /**
     * @param string $tagName
     * @return $this
     */
    public function setNewTagName(string $tagName): self
    {
        $this->postData['name'] = $tagName;

        return $this;
    }

    /**
     * @param string $mergeHash
     * @return $this
     */
    public function setTargetHashName(string $mergeHash): self
    {
        $this->postData['target'] = ['hash' => $mergeHash];

        return $this;
    }

}
