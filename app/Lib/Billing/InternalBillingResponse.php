<?php


namespace App\Lib\Billing;


/**
 * Translate the response that comes out of recharge functions.
 * Class InternalBillingResponse
 * @package App\Lib\Billing
 */
class InternalBillingResponse
{
    /**
     * @var bool $successStatus
     */
    protected bool $successStatus = false;

    /**
     * @var string|null $authorizationId
     */
    protected ?string $authorizationId = null;

    /**
     * @var string|null $transactionId
     */
    protected ?string $transactionId = null;

    /**
     * @var string $errorMessage
     */
    protected string $errorMessage = '';

    /**
     * @var string|null $responseCode
     */
    protected ?string $responseCode = null;

    /**
     * @var string $status
     */
    protected string $status = '';

    /**
     * @var string|null $actionType
     */
    protected ?string $actionType = null;

    /**
     * @var string|null $processorId
     */
    protected ?string $processorId = null;

    /**
     * @var int $newOrderId
     */
    protected int $newOrderId = 0;

    /**
     * @var bool $usesBillingModels
     */
    protected bool $usesBillingModels = false;

    /**
     * @var float|null $totalOrderAmount
     */
    protected ?float $totalOrderAmount = null;

    /**
     * @var bool $isErrorFound
     */
    protected bool $isErrorFound = false;

    /**
     * @var string|mixed $declineReason
     */
    protected string $declineReason = '';

    /**
     * @var string $responseMessage
     */
    protected string $responseMessage = '';

    /**
     * InternalBillingResponse constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (isset($data['authId'])) {
            $this->authorizationId = $data['authId'];
        }

        if (isset($data['errorFound'])) {
            $this->isErrorFound = $data['errorFound'] == 1;
        }

        if (isset($data['errorMessage'])) {
            $this->errorMessage = $data['errorMessage'];
        }

        if (isset($data['responseMessage'])) {
            $this->responseMessage = $data['responseMessage'];
        }

        if (isset($data['declineReason'])) {
            $this->declineReason = $data['declineReason'];
        }

        if (isset($data['resp_code'])) {
            $this->responseCode = $data['resp_code'];
        } else if (isset($data['responseCode'])) {
            $this->responseCode = $data['responseCode'];
        }

        if (isset($data['status'])) {
            $this->status = strtolower($data['status']);
        }

        if (isset($data['transId'])) {
            $this->transactionId = $data['transId'];
        } else if ($data['transactionID']) {
            $this->transactionId = $data['transactionID'];
        }

        if (isset($data['action_type'])) {
            $this->actionType = $data['action_type'];
        }

        if (isset($data['newid'])) {
            $this->newOrderId = $data['newid'];
        } else if (isset($data['orderId'])) {
            $this->newOrderId = $data['orderId'];
        }

        if (isset($data['uses_billing_models'])) {
            $this->usesBillingModels = (bool) $data['uses_billing_models'];
        }

        if (isset($data['totalOrderAmount'])) {
            $this->totalOrderAmount = (float) $data['totalOrderAmount'];
        }

        $this->successStatus = ($this->status === 'ok');
    }

    /**
     * @return bool
     */
    public function isSuccessStatus(): bool
    {
        return $this->successStatus;
    }

    /**
     * @return string|null
     */
    public function getAuthorizationId(): ?string
    {
        return $this->authorizationId;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    /**
     * @return string|null
     */
    public function getProcessorId(): ?string
    {
        return $this->processorId;
    }

    /**
     * @return int
     */
    public function getNewOrderId(): int
    {
        return $this->newOrderId;
    }

    /**
     * @return bool
     */
    public function isUsesBillingModels(): bool
    {
        return $this->usesBillingModels;
    }

    /**
     * @return float|null
     */
    public function getTotalOrderAmount(): ?float
    {
        return $this->totalOrderAmount;
    }
}