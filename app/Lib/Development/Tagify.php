<?php

namespace App\Lib\Development;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Lib\Development\BitBucket\BitBucketApi;
use App\Lib\Development\BitBucket\IncomingWebhook;
use App\Lib\Development\Interfaces\Repositories\RepoApiClient;

class Tagify
{

    public const LEGACY_REPO             = 'limelightcrm/legacy';
    public const PRE_RELEASE_BRANCH      = 'pre-release';
    public const HOTFIX_BRANCH           = 'hotfix';
    public const RELEASE_BRANCH          = 'release';

    public const PRE_RELEASE_TAG_PATTERN = '/^(\d{4}\.\d{2}\.\d{2})\.(\d{2})$/';
    public const RELEASE_TAG_PATTERN     = '/^IMPLEMENT_LATER$/'; //@TODO implement later with DEV-695, placeholder for now

    public const BRANCH_FLAGS            = [
        self::PRE_RELEASE_BRANCH => '-p',
        self::HOTFIX_BRANCH      => '-h',
        self::RELEASE_BRANCH     => '-r',
    ];

    public const TAG_PATTERNS            = [
        self::PRE_RELEASE_BRANCH => self::PRE_RELEASE_TAG_PATTERN,
        self::HOTFIX_BRANCH      => self::RELEASE_TAG_PATTERN,
        self::RELEASE_BRANCH     => self::RELEASE_TAG_PATTERN,
    ];

    /**
     * Array of branch names which are allowed to
     *
     * @var array|null
     */
    public ?array $allowedTargetBranches = [];

    /**
     * @var ?RepoApiClient
     */
    protected ?RepoApiClient $repo = null;

    /**
     * Tagify constructor.
     */
    public function __construct()
    {
        $this->allowedTargetBranches = config('tagify.allowedBranches');
    }

    /**
     * Initialize the api client that will talk to our git repo
     * @param string|null $repoName
     */
    public function initializeRepoApi(?string $repoName = null): void
    {
        $this->repo = new BitBucketApi(null, null, null, $repoName);
    }

    /**
     * @param string $newTagName
     * @param string $mergeCommit
     * @param string|null $repoName
     * @return bool
     */
    protected function pushNewTagForCommit(string $newTagName, string $mergeCommit, ?string $repoName): bool
    {
        $apiResponse = $this->getRepo($repoName)->pushTag($newTagName, $mergeCommit);

        return $apiResponse->successful();
    }

    /**
     * @param string      $branch
     * @param string|null $repoName
     * @return string|null
     */
    protected function getNextTagForBranch(string $branch, ?string $repoName): ?string
    {
        $filter        = $this->getFilterForBranch($branch);
        $tagCollection = $this->getRepo($repoName)->getTags($filter, '-name')->get();

        return $this->getNextTag($tagCollection, $branch);
    }

    /**
     * @param Collection $tags
     * @param string $branch
     * @return string|null
     */
    protected function getNextTag(Collection $tags, string $branch): ?string
    {
        $lastTag = null;
        $nextTag = null;
        $pattern = self::TAG_PATTERNS[$branch] ?? self::PRE_RELEASE_TAG_PATTERN;
        $tags->each(function ($tag, $key) use ($pattern, $lastTag, &$nextTag) {
            $tagName = $tag['name'];
            if (preg_match($pattern, $tagName, $matches)) {
                $lastTag = $tagName;
                if ($pattern === self::PRE_RELEASE_TAG_PATTERN) {
                    $nextTag = $this->getNextPrereleaseTag($lastTag);
                }

                return false;
            }
        });

        if (empty(self::TAG_PATTERNS[$branch])) {
            // any non patterned branches that come through will use the pre-release tag pattern but will
            // append the branch name so as not to interfere with normal tagging processes
            // these will most likely be test/debugging calls
            $nextTag = "$nextTag-$branch";
        }

        return $nextTag;
    }

    /**
     * @param string $lastTag
     * @return string|null
     */
    protected function getNextPrereleaseTag(string $lastTag): ?string
    {
        $nextTag = null;
        if (preg_match(self::PRE_RELEASE_TAG_PATTERN, $lastTag, $matches)) {
            $tagDate    = $matches[1];
            $tagVersion = (int) $matches[2];
            $today      = Carbon::now()->format('Y.m.d');
            // increment version number or start over for a new day
            if ($today !== $tagDate) {
                $tagVersion = 1;
            } else {
                $tagVersion++;
            }
            $tagVersion = str_pad($tagVersion, 2, '0', STR_PAD_LEFT);
            $nextTag    = "$today.$tagVersion";
        }

        return $nextTag;
    }

    /**
     * @param string $branch
     * @return string|null
     */
    protected function getFilterForBranch(string $branch): ?string
    {
        $filter = null;
        switch (true) {
            case $branch === self::HOTFIX_BRANCH:
            case $branch === self::RELEASE_BRANCH:
                // @TODO Implement later with DEV-695
                break;
            case $branch === self::PRE_RELEASE_BRANCH:
            default:
                $thisYear = (int) Carbon::now()->format('Y');
                $lastYear = $thisYear - 1;
                $filter   = "name ~ \"$thisYear.\"  OR name ~ \"$lastYear.\"";
                break;
        }

        return $filter;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function tag(array $params): bool
    {
        Log::debug('Tagify: '.__METHOD__.' called with payload', ['tagifyPayload' => $params]);
        $successful   = false;
        $webhook      = new IncomingWebhook($params);
        $targetBranch = $webhook->getTargetBranch();
        $mergeCommit  = $webhook->getMergeCommit();
        $targetRepo   = $webhook->getTargetRepoShortName();

        if (! in_array($targetBranch, $this->allowedTargetBranches, true)) {
            Log::debug('Tagify: '.__METHOD__." $targetBranch is not permitted. Please add to TAGIFY_BRANCHES in .env");
        } else {
            $newTagName = $this->getNextTagForBranch($targetBranch, $targetRepo);
            $successful = $this->pushNewTagForCommit($newTagName, $mergeCommit, $targetRepo);
            if (! $successful) {
                Log::debug('Tagify: '.__METHOD__.' failed to push new tag', [
                    'newTagName'  => $newTagName,
                    'repo'        => $targetRepo,
                    'mergeCommit' => $mergeCommit,
                ]);
            }
            $notified = $this->sendTagNotification($webhook, $newTagName, $successful);
            if (! $notified) {
                $message = $successful ? 'successfully created tag' : 'tag creation failure';
                Log::debug('Tagify: '.__METHOD__." failed to notify the team of a $message", [
                    'newTagName'  => $newTagName,
                    'repo'        => $targetRepo,
                    'mergeCommit' => $mergeCommit,
                ]);
            }
        }
        Log::debug('Tagify: '.__METHOD__.' responded with', ['tagifySuccessful' => $successful]);

        return $successful;
    }

    /**
     * @param IncomingWebhook $webhook
     * @param string $newTagName
     * @param bool $successful
     * @param string|null $url
     * @return bool
     */
    protected function sendTagNotification(IncomingWebhook $webhook, string $newTagName, bool $successful = true, ?string $url = null): bool
    {
        $response     = null;
        $slackPayload = $this->getTagNotificationPayload($webhook, $newTagName, $successful);
        $url          = $url ?? (string) config('tagify.notificationsUrl');
        $slack        = new Client();
        $response     = $slack->post($url, ['body' => json_encode($slackPayload)]);

        return $response && $response->getStatusCode() === 200;
    }

    /**
     * @param IncomingWebhook $webhook
     * @param string $newTagName
     * @param bool $successful
     * @return array
     */
    protected function getTagNotificationPayload(IncomingWebhook $webhook, string $newTagName, bool $successful): array
    {
        $blocks      = [];
        $tagMessage  = '';
        $slackHelper = new SlackHelper;
        if ($successful) {
            $gitImage = config('tagify.successImage');
            $blocks[] = $slackHelper->getTitleSection('@here New tag created.', $gitImage);
        } else {
            $logFile    = storage_path('logs/lumen.log');
            $cautionImg = config('tagify.failureImage');
            $text       = "@here Failed to create new tag!\n";
            $text       .= "For more information see:\n";
            $text       .= "`$logFile`\n";
            $text       .= "PLEASE MANUALLY CREATE A TAG FOR THE BELOW.";
            $blocks[]   = $slackHelper->getTitleSection($text, $cautionImg);
            $tagMessage = 'NOT CREATED ';
        }
        $blocks[]     = $slackHelper->getDivider();
        $blocks[]     = $slackHelper->getHorizontalField("Tag Name", "$tagMessage`$newTagName`");
        $targetBranch = $webhook->getTargetBranch();
        $sourceBranch = $webhook->getSourceBranch();
        $branches     = "`$sourceBranch` --> `$targetBranch`";
        $blocks[]     = $slackHelper->getHorizontalField("Merged", $branches);
        $prLink       = $webhook->getPrLink();
        $prTitle      = $webhook->getPrTitle();
        $link         = "<$prLink|$prTitle>";
        $blocks[]     = $slackHelper->getHorizontalField("Pull Request", $link);
        $blocks[]     = $slackHelper->getHorizontalField("Target Repo", $webhook->getTargetRepo());
        $blocks[]     = $slackHelper->getHorizontalField("Author", $webhook->getAuthor());
        $blocks[]     = $slackHelper->getHorizontalField("Merged By", $webhook->getActor());
        $approvers    = implode(',', $webhook->getPrApprovers()) ?: 'None';
        $blocks[]     = $slackHelper->getHorizontalField("Approved By", $approvers);
        $commitHash   = $webhook->getMergeCommit();
        $blocks[]     = $slackHelper->getHorizontalField("Merge Hash", "`$commitHash`");
        $blocks[]     = $slackHelper->getDivider();

        return $slackHelper->getPayload($blocks);
    }

    /**
     * @return RepoApiClient
     */
    public function getRepo($repoName = null): RepoApiClient
    {
        if ($this->repo instanceof RepoApiClient) {
            $this->repo->setRepo($repoName);
        } else {
            $this->initializeRepoApi($repoName);
        }

        return $this->repo;
    }
}

