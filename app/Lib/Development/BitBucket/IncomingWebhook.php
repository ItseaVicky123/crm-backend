<?php

namespace App\Lib\Development\BitBucket;

use Illuminate\Support\Arr;
use App\Lib\Development\Tagify;

/**
 * Class IncomingWebhook
 *
 * @package App\Lib\Utilities\BitBucket
 */
class IncomingWebhook
{
    public const PR_STATUS_MERGED = 'MERGED';
    public const PR_TYPE          = 'pullrequest';

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * IncomingWebhook constructor.
     *
     * @param array $webhookParams
     */
    public function __construct(array $webhookParams)
    {
        $this->params = $webhookParams;
    }

    /**
     * @return bool
     */
    public function isMerge(): bool
    {
        $prKey    = self::PR_TYPE;
        $isPrType = Arr::get($this->params, "$prKey.type") === $prKey;
        return $isPrType && Arr::get($this->params, "$prKey.state") === self::PR_STATUS_MERGED;
    }

    /**
     * @param string $targetBranch
     * @param string $targetRepo   The full name of the target repo
     * @return bool
     */
    public function hasMergeTarget(string $targetBranch, string $targetRepo = Tagify::LEGACY_REPO): bool
    {
        $hasTargetRepo = $this->getTargetRepo() === $targetRepo;
        return $hasTargetRepo && $this->getTargetBranch() === $targetBranch;
    }

    /**
     * @return string
     */
    public function getTargetRepo(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.destination.repository.full_name', '');
    }

    /**
     * @return string
     */
    public function getSourceRepo(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.source.repository.full_name', '');
    }

    /**
     * @return string
     */
    public function getTargetRepoShortName(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.destination.repository.name', '');
    }

    /**
     * @return array
     */
    public function getPrReviewers(): array
    {
        return Arr::get($this->params, self::PR_TYPE.'.participants', []);
    }

    /**
     * @return array
     */
    public function getPrApprovers(): array
    {
        $approvers = [];
        $reviewers = $this->getPrReviewers();
        foreach ($reviewers as $reviewer) {
            if ($approved = $reviewer['approved'] ?? false) {
                $approvers[]= Arr::get($reviewer, 'user.display_name');
            }
        }
        return $approvers;
    }

    /**
     * @return string
     */
    public function getPrTitle(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.rendered.title.raw', '');
    }

    /**
     * @return string
     */
    public function getSourceBranch(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.source.branch.name', '');
    }


    /**
     * @return string
     */
    public function getTargetBranch(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.destination.branch.name', '');
    }

    /**
     * @return string
     */
    public function getActor(): string
    {
        return Arr::get($this->params, 'actor.display_name', '');
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.author.display_name', '');
    }

    /**
     * @return string
     */
    public function getMergeCommit(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.merge_commit.hash', '');
    }

    /**
     * @return string
     */
    public function getPrLink(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.links.html.href', '');
    }

    /**
     * @return string
     */
    public function getMergeSourceCommit(): string
    {
        return Arr::get($this->params, self::PR_TYPE.'.source.commit.hash', '');
    }

    public function getMergeData(): array
    {
        if (!$this->isMerge()) {
            $data = [];
        } else {
            $data = [
                'title'           => $this->getPrTitle(),
                'pullRequestLink' => $this->getPrLink(),
                'author'          => $this->getAuthor(),
                'actor'           => $this->getActor(),
                'approvers'       => $this->getPrApprovers(),
                'repo'            => $this->getTargetRepo(),
                'sourceBranch'    => $this->getSourceBranch(),
                'targetBranch'    => $this->getTargetBranch(),
                'mergeCommit'     => $this->getMergeCommit(),
            ];
        }
        return $data;
    }

}
