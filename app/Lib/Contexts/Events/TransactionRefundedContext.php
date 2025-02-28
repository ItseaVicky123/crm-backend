<?php

namespace App\Lib\Contexts\Events;


/**
 * Class TransactionRefundedContext
 * @package App\Lib\Contexts\Events
 */
class TransactionRefundedContext extends EventContext
{
    use CancelShipmentContextTrait;

    /*Initiated values */
    const INTERNALLY = 'internally';
    const EXTERNALLY = 'externally';
    /**/

    /**
     * @var bool $shouldCommunicateToCostumer
     */
    protected bool $shouldCommunicateToCostumer = true;

    /**
     * @var string $initiatedBy
     */
    protected string $initiatedBy = self::INTERNALLY;

    /**
     * @return string
     */
    public function getInitiatedBy(): string
    {
        return $this->initiatedBy;
    }

    /**
     * @param string $initiatedBy
     */
    public function setInitiatedBy(string $initiatedBy): void
    {
        $this->initiatedBy = $initiatedBy;
    }

    /**
     * Determine whether we should send communication to end customer or not
     * @return bool
     */
    public function shouldCommunicateToCostumer(): bool
    {
        return $this->shouldCommunicateToCostumer;
    }

    /**
     * @param bool $shouldCommunicateToCostumer
     * @return $this
     */
    public function setShouldCommunicateToCostumer(bool $shouldCommunicateToCostumer): self
    {
        $this->shouldCommunicateToCostumer = $shouldCommunicateToCostumer;

        return $this;
    }
}
