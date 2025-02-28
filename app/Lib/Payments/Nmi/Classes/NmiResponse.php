<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Traits\TransactionType;
use App\Lib\Payments\Nmi\Interfaces\NmiResponseProvider;

class NmiResponse implements NmiResponseProvider
{
    use TransactionType;

    public const RESPONSE_STATUS_APPROVED = 1;
    public const RESPONSE_STATUS_DECLINED = 2;
    public const RESPONSE_STATUS_ERROR    = 3;

    public const RESPONSE_CODE_APPROVED                      = 100;
    public const RESPONSE_CODE_DECLINED_BY_PROCESSOR         = 200;
    public const RESPONSE_CODE_DO_NOT_HONOR                  = 201;
    public const RESPONSE_CODE_INSUFFICIENT_FUNDS            = 202;
    public const RESPONSE_CODE_OVER_LIMIT                    = 203;
    public const RESPONSE_CODE_NOT_ALLOWED                   = 204;
    public const RESPONSE_CODE_INVALID_PAYMENT_INFO          = 220;
    public const RESPONSE_CODE_INVALID_ISSUER                = 221;
    public const RESPONSE_CODE_INVALID_CC_NUMBER             = 222;
    public const RESPONSE_CODE_EXPIRED_CC                    = 223;
    public const RESPONSE_CODE_INVALID_CC_EXP                = 224;
    public const RESPONSE_CODE_INVALID_CC_CVV                = 225;
    public const RESPONSE_CODE_INVALID_CC_PIN                = 226;
    public const RESPONSE_CODE_CALL_ISSUER                   = 240;
    public const RESPONSE_CODE_PICK_UP_CARD                  = 250;
    public const RESPONSE_CODE_LOST_CC                       = 251;
    public const RESPONSE_CODE_STOLEN_CC                     = 252;
    public const RESPONSE_CODE_FRAUDULENT_CC                 = 253;
    public const RESPONSE_CODE_DECLINED_WITH_INFO            = 260;
    public const RESPONSE_CODE_DECLINED_STOP_ALL_RECURRING   = 261;
    public const RESPONSE_CODE_DECLINED_STOP_THIS_RECURRING  = 262;
    public const RESPONSE_CODE_DECLINED_UPDATE_CARDHOLDER    = 263;
    public const RESPONSE_CODE_RETRY_LATER                   = 264;
    public const RESPONSE_CODE_GATEWAY_REJECTED              = 300;
    public const RESPONSE_CODE_PROCESSOR_ERROR               = 400;
    public const RESPONSE_CODE_INVALID_MERCHANT_CONFIG       = 410;
    public const RESPONSE_CODE_INACTIVE_MERCHANT             = 411;
    public const RESPONSE_CODE_COMMUNICATION_ERROR           = 420;
    public const RESPONSE_CODE_ISSUER_COMMUNICATION_ERROR    = 421;
    public const RESPONSE_CODE_PROCESSOR_DUPLICATE           = 430;
    public const RESPONSE_CODE_PROCESSOR_FORMAT_ERROR        = 440;
    public const RESPONSE_CODE_INVALID_TRANSACTION_INFO      = 441;
    public const RESPONSE_CODE_PROCESSOR_FEATURE_UNAVAILABLE = 460;
    public const RESPONSE_CODE_UNSUPPORTED_CC_TYPE           = 461;

    public const RESPONSE_CODE_MAP = [
        self::RESPONSE_CODE_APPROVED                      => 'Transaction was approved',
        self::RESPONSE_CODE_DECLINED_BY_PROCESSOR         => 'Transaction was declined by processor',
        self::RESPONSE_CODE_DO_NOT_HONOR                  => 'Do not honor',
        self::RESPONSE_CODE_INSUFFICIENT_FUNDS            => 'Insufficient funds',
        self::RESPONSE_CODE_OVER_LIMIT                    => 'Over limit',
        self::RESPONSE_CODE_NOT_ALLOWED                   => 'Transaction not allowed',
        self::RESPONSE_CODE_INVALID_PAYMENT_INFO          => 'Incorrect payment information',
        self::RESPONSE_CODE_INVALID_ISSUER                => 'No such card issuer',
        self::RESPONSE_CODE_INVALID_CC_NUMBER             => 'No card number on file with issuer',
        self::RESPONSE_CODE_EXPIRED_CC                    => 'Expired card',
        self::RESPONSE_CODE_INVALID_CC_EXP                => 'Invalid expiration date',
        self::RESPONSE_CODE_INVALID_CC_CVV                => 'Invalid card security code',
        self::RESPONSE_CODE_INVALID_CC_PIN                => 'Invalid PIN',
        self::RESPONSE_CODE_CALL_ISSUER                   => 'Call issuer for further information',
        self::RESPONSE_CODE_PICK_UP_CARD                  => 'Pick up card',
        self::RESPONSE_CODE_LOST_CC                       => 'Lost card',
        self::RESPONSE_CODE_STOLEN_CC                     => 'Stolen card',
        self::RESPONSE_CODE_FRAUDULENT_CC                 => 'Fraudulent card',
        self::RESPONSE_CODE_DECLINED_WITH_INFO            => 'Declined with further instructions available. (See response text)',
        self::RESPONSE_CODE_DECLINED_STOP_ALL_RECURRING   => 'Declined-Stop all recurring payments',
        self::RESPONSE_CODE_DECLINED_STOP_THIS_RECURRING  => 'Declined-Stop this recurring program',
        self::RESPONSE_CODE_DECLINED_UPDATE_CARDHOLDER    => 'Declined-Update cardholder data available',
        self::RESPONSE_CODE_RETRY_LATER                   => 'Declined-Retry in a few days',
        self::RESPONSE_CODE_GATEWAY_REJECTED              => 'Transaction was rejected by gateway',
        self::RESPONSE_CODE_PROCESSOR_ERROR               => 'Transaction error returned by processor',
        self::RESPONSE_CODE_INVALID_MERCHANT_CONFIG       => 'Invalid merchant configuration',
        self::RESPONSE_CODE_INACTIVE_MERCHANT             => 'Merchant account is inactive',
        self::RESPONSE_CODE_COMMUNICATION_ERROR           => 'Communication error',
        self::RESPONSE_CODE_ISSUER_COMMUNICATION_ERROR    => 'Communication error with issuer',
        self::RESPONSE_CODE_PROCESSOR_DUPLICATE           => 'Duplicate transaction at processor',
        self::RESPONSE_CODE_PROCESSOR_FORMAT_ERROR        => 'Processor format error',
        self::RESPONSE_CODE_INVALID_TRANSACTION_INFO      => 'Invalid transaction information',
        self::RESPONSE_CODE_PROCESSOR_FEATURE_UNAVAILABLE => 'Processor feature not available',
        self::RESPONSE_CODE_UNSUPPORTED_CC_TYPE           => 'Unsupported card type',
    ];

    public const CVV_RESPONSE_CODE_MATCH         = 'M';
    public const CVV_RESPONSE_CODE_NO_MATCH      = 'N';
    public const CVV_RESPONSE_CODE_NOT_PROCESSED = 'P';
    public const CVV_RESPONSE_CODE_NOT_AVAILABLE = 'S';
    public const CVV_RESPONSE_CODE_NOT_CERTIFIED = 'U';

    public const CVV_RESPONSE_CODE_MAP = [
        self::CVV_RESPONSE_CODE_MATCH         => 'CVV2/CVC2 match',
        self::CVV_RESPONSE_CODE_NO_MATCH      => 'CVV2/CVC2 no match',
        self::CVV_RESPONSE_CODE_NOT_PROCESSED => 'Not processed',
        self::CVV_RESPONSE_CODE_NOT_AVAILABLE => 'Merchant has indicated that CVV2/CVC2 is not present on card',
        self::CVV_RESPONSE_CODE_NOT_CERTIFIED => 'Issuer is not certified and/or has not provided Visa encryption keys',
    ];

    public const CVV_UNAVAILABLE_RESPONSE_CODES = [
        self::CVV_RESPONSE_CODE_NOT_PROCESSED,
        self::CVV_RESPONSE_CODE_NOT_AVAILABLE,
        self::CVV_RESPONSE_CODE_NOT_CERTIFIED,
    ];

    public const AVS_RESPONSE_CODE_EXACT_MATCH_X = 'X';
    public const AVS_RESPONSE_CODE_EXACT_MATCH_Y = 'Y';
    public const AVS_RESPONSE_CODE_EXACT_MATCH_D = 'D';
    public const AVS_RESPONSE_CODE_EXACT_MATCH_M = 'M';
    public const AVS_RESPONSE_CODE_EXACT_MATCH_2 = '2';
    public const AVS_RESPONSE_CODE_EXACT_MATCH_6 = '6';

    public const AVS_RESPONSE_ADDRESS_ONLY_MATCH_A      = 'A';
    public const AVS_RESPONSE_ADDRESS_ONLY_MATCH_B      = 'B';
    public const AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_3 = '3';
    public const AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_7 = '7';

    public const AVS_RESPONSE_ZIP_ONLY_MATCH_W = 'W';
    public const AVS_RESPONSE_ZIP_ONLY_MATCH_Z = 'Z';
    public const AVS_RESPONSE_ZIP_ONLY_MATCH_P = 'P';
    public const AVS_RESPONSE_ZIP_ONLY_MATCH_L = 'L';

    public const AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_1 = '1';
    public const AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_5 = '5';
    public const AVS_RESPONSE_ZIP_ONLY_MATCH_N      = 'N';
    public const AVS_RESPONSE_ZIP_ONLY_MATCH_C      = 'C';
    public const AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_4 = '4';
    public const AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_8 = '8';

    public const AVS_RESPONSE_UNAVAILABLE_U = 'U';
    public const AVS_RESPONSE_UNAVAILABLE_G = 'G';
    public const AVS_RESPONSE_UNAVAILABLE_I = 'I';
    public const AVS_RESPONSE_UNAVAILABLE_R = 'R';
    public const AVS_RESPONSE_UNAVAILABLE_S = 'S';
    public const AVS_RESPONSE_UNAVAILABLE_0 = '0';
    public const AVS_RESPONSE_UNAVAILABLE_O = 'O';
    public const AVS_RESPONSE_UNAVAILABLE_E = 'E';

    public const AVS_EXACT_MATCH_RESPONSE_CODES = [
        self::AVS_RESPONSE_CODE_EXACT_MATCH_X,
        self::AVS_RESPONSE_CODE_EXACT_MATCH_Y,
        self::AVS_RESPONSE_CODE_EXACT_MATCH_D,
        self::AVS_RESPONSE_CODE_EXACT_MATCH_M,
        self::AVS_RESPONSE_CODE_EXACT_MATCH_2,
        self::AVS_RESPONSE_CODE_EXACT_MATCH_6,
    ];

    public const AVS_PARTIAL_MATCH_RESPONSE_CODES = [
        self::AVS_RESPONSE_ADDRESS_ONLY_MATCH_A,
        self::AVS_RESPONSE_ADDRESS_ONLY_MATCH_B,
        self::AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_3,
        self::AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_7,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_W,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_Z,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_P,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_L,
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_1,
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_5,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_N,
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_C,
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_4,
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_8,
    ];

    public const AVS_UNAVAILABLE_RESPONSE_CODES = [
        self::AVS_RESPONSE_UNAVAILABLE_U,
        self::AVS_RESPONSE_UNAVAILABLE_G,
        self::AVS_RESPONSE_UNAVAILABLE_I,
        self::AVS_RESPONSE_UNAVAILABLE_R,
        self::AVS_RESPONSE_UNAVAILABLE_S,
        self::AVS_RESPONSE_UNAVAILABLE_0,
        self::AVS_RESPONSE_UNAVAILABLE_O,
        self::AVS_RESPONSE_UNAVAILABLE_E,
    ];

    public const AVS_RESPONSE_CODE_MAP = [
        self::AVS_RESPONSE_CODE_EXACT_MATCH_X        => 'Exact match, 9-character numeric ZIP',
        self::AVS_RESPONSE_CODE_EXACT_MATCH_Y        => 'Exact match, 5-character numeric ZIP',
        self::AVS_RESPONSE_CODE_EXACT_MATCH_D        => 'Exact match, 5-character numeric ZIP',
        self::AVS_RESPONSE_CODE_EXACT_MATCH_M        => '5-character numeric ZIP',
        self::AVS_RESPONSE_CODE_EXACT_MATCH_2        => 'Exact match, 5-character numeric ZIP, customer name',
        self::AVS_RESPONSE_CODE_EXACT_MATCH_6        => 'Exact match, 5-character numeric ZIP, customer name',
        self::AVS_RESPONSE_ADDRESS_ONLY_MATCH_A      => 'Address match only',
        self::AVS_RESPONSE_ADDRESS_ONLY_MATCH_B      => 'Address match only',
        self::AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_3 => 'Address, customer name match only',
        self::AVS_RESPONSE_ADDRESS_NAME_ONLY_MATCH_7 => 'Address, customer name match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_W          => '9-character numeric ZIP match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_Z          => '5-character ZIP match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_P          => '5-character ZIP match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_L          => '5-character ZIP match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_N          => 'No address or ZIP match only',
        self::AVS_RESPONSE_ZIP_ONLY_MATCH_C          => 'No address or ZIP match only',
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_1     => '5-character ZIP, customer name match only',
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_5     => '5-character ZIP, customer name match only',
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_4     => 'No address or ZIP or customer name match only',
        self::AVS_RESPONSE_ZIP_NAME_ONLY_MATCH_8     => 'No address or ZIP or customer name match only',
        self::AVS_RESPONSE_UNAVAILABLE_U             => 'Address unavailable',
        self::AVS_RESPONSE_UNAVAILABLE_G             => 'Non-U.S. issuer does not participate',
        self::AVS_RESPONSE_UNAVAILABLE_I             => 'Non-U.S. issuer does not participate',
        self::AVS_RESPONSE_UNAVAILABLE_R             => 'Issuer system unavailable',
        self::AVS_RESPONSE_UNAVAILABLE_S             => 'Service not supported',
        self::AVS_RESPONSE_UNAVAILABLE_0             => 'AVS not available',
        self::AVS_RESPONSE_UNAVAILABLE_O             => 'AVS not available',
        self::AVS_RESPONSE_UNAVAILABLE_E             => 'Not a mail/phone order',
    ];

    /**
     * Raw response from the API call
     * @var string|null
     */
    protected ?string $rawResponse = null;

    /**
     * The NMI response code for the processed transaction.
     * Values: one of the RESPONSE_STATUS_X constants
     * @var int|null
     */
    protected ?int $response = null;

    /**
     * Textual response
     * @var string|null
     */
    protected ?string $responseText = null;

    /**
     * Transaction authorization code.
     * @var string|null
     */
    protected ?string $authCode = null;

    /**
     * Payment gateway transaction id.
     * @var string|null
     */
    protected ?string $transactionId = null;

    /**
     * AVS response code
     * Values: one of the AVS_RESPONSE_CODE_X constants
     * @var string|null
     */
    protected ?string $avsResponse = null;

    /**
     * CVV response code
     * Values: one of the CVV_RESPONSE_CODE_X constants
     * @var string|null
     */
    protected ?string $cvvResponse = null;

    /**
     * The original merchant order id passed in the transaction request.
     * @var string|null
     */
    protected ?string $orderId = null;

    /**
     * The customer vault id created by or used in the transaction request.
     * @var string|null
     */
    protected ?string $customerVaultId = null;

    /**
     * CC number used in the transaction request, Obfuscated like `4xxxxxxxxxxx1111`
     * @var string|null
     */
    protected ?string $ccNumber = null;

    /**
     * The customer's bank routing number used in the transaction request.
     * @var string|null
     */
    protected ?string $checkAba = null;

    /**
     * The customer's bank account number used in the transaction request.
     * @var string|null
     */
    protected ?string $checkAccount = null;

    /**
     * Numeric mapping of processor responses .
     * Values: one of the RESPONSE_CODE_X constants
     * @var int|null
     */
    protected ?int $responseCode = null;

    /**
     * This will optionally come back when any chip card data is provided on the authorization.
     * This data needs to be sent back to the SDK after an authorization.
     * @var int|null
     */
    protected ?int $emvAuthResponseData = null;

    /**
     * @var bool|null
     */
    protected ?bool $avsExactMatch = null;

    /**
     * @var bool|null
     */
    protected ?bool $avsUnavailable = null;

    /**
     * @var bool|null
     */
    protected ?bool $cvvMatch = null;

    /**
     * @var bool|null
     */
    protected ?bool $cvvUnavailable = null;

    /**
     * @var bool|null
     */
    protected ?bool $avsPartialMatch = null;

    /**
     * @var string|null
     */
    protected ?string $avsMessage = null;

    /**
     * @var string|null
     */
    protected ?string $cvvMessage = null;

    /**
     * @var string|null
     */
    protected ?string $transactionMessage = null;

    /**
     * @var string|null
     */
    protected ?string $processorId = null;

    /**
     * @var bool|null
     */
    protected ?bool $successful = null;

    /**
     * Get the human readable text for the given response code
     * @param string|int $responseCode
     * @return string
     */
    public function getResponseCodeText($responseCode): string
    {
        return self::RESPONSE_CODE_MAP[$responseCode] ?: '';
    }

    /**
     * Get the human readable text for the given CVV response code
     * @param string|int $responseCode
     * @return string
     */
    public function getCvvResponseCodeText($responseCode): string
    {
        return self::CVV_RESPONSE_CODE_MAP[$responseCode] ?: '';
    }

    /**
     * Get the human readable text for the given AVS response code
     * @param string|int $responseCode
     * @return string
     */
    public function getAvsResponseCodeText($responseCode): string
    {
        return self::AVS_RESPONSE_CODE_MAP[$responseCode] ?: '';
    }

    /**
     * Check if the given AVS response code indicates an exact AVS match
     * @param string|int $responseCode
     * @return string
     */
    public function isExactAvsMatch($responseCode): string
    {
        return in_array((string)$responseCode, self::AVS_EXACT_MATCH_RESPONSE_CODES, true);
    }

    /**
     * Check if the given AVS response code indicates an partial AVS match
     * @param string|int $responseCode
     * @return string
     */
    public function isPartialAvsMatch($responseCode): string
    {
        return in_array((string)$responseCode, self::AVS_PARTIAL_MATCH_RESPONSE_CODES, true);
    }

    /**
     * Check if the given AVS response code indicates AVS is unavailable for the cc used
     * @param string|int $responseCode
     * @return string
     */
    public function isAvsUnavailable($responseCode): string
    {
        return in_array((string)$responseCode, self::AVS_UNAVAILABLE_RESPONSE_CODES, true);
    }


    /**
     * Check if the given CVV response code indicates CVV is unavailable for the cc used
     * @param string|int $responseCode
     * @return string
     */
    public function isCvvUnavailable($responseCode): string
    {
        return in_array((string)$responseCode, self::CVV_UNAVAILABLE_RESPONSE_CODES, true);
    }

    /**
     * Check if the given CVV response code indicates a CVV match
     * @param string|int $responseCode
     * @return string
     */
    public function isCvvMatch($responseCode): string
    {
        return $responseCode === self::CVV_RESPONSE_CODE_MATCH;
    }

    /**
     * @return string|null
     */
    public function getRawResponse(): ?string
    {
        return $this->rawResponse ;
    }

    /**
     * @return int|null
     */
    public function getResponse(): ?int
    {
        return $this->response;
    }

    /**
     * @return string|null
     */
    public function getResponseText(): ?string
    {
        return $this->responseText;
    }

    /**
     * @return string|null
     */
    public function getAuthCode(): ?string
    {
        return $this->authCode;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @return string|null
     */
    public function getAvsResponse(): ?string
    {
        return $this->avsResponse;
    }

    /**
     * @return string|null
     */
    public function getCvvResponse(): ?string
    {
        return $this->cvvResponse;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @return int|null
     */
    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    /**
     * @return int|null
     */
    public function getEmvAuthResponseData(): ?int
    {
        return $this->emvAuthResponseData;
    }

    /**
     * @return string|null
     */
    public function getCustomerVaultId(): ?string
    {
        return $this->customerVaultId;
    }

    /**
     * @return string|null
     */
    public function getCcNumber(): ?string
    {
        return $this->ccNumber;
    }

    /**
     * @return string|null
     */
    public function getCheckaba(): ?string
    {
        return $this->checkAba;
    }

    /**
     * @return string|null
     */
    public function getCheckAccount(): ?string
    {
        return $this->checkAccount;
    }

    /**
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
     * @return bool|null
     */
    public function getAvsExactMatch(): ?bool
    {
        return $this->avsExactMatch;
    }

    /**
     * @return bool|null
     */
    public function getAvsUnavailable(): ?bool
    {
        return $this->avsUnavailable;
    }

    /**
     * @return bool|null
     */
    public function getCvvMatch(): ?bool
    {
        return $this->cvvMatch;
    }

    /**
     * @return bool|null
     */
    public function getCvvUnavailable(): ?bool
    {
        return $this->cvvUnavailable;
    }

    /**
     * @return bool|null
     */
    public function getAvsPartialMatch(): ?bool
    {
        return $this->avsPartialMatch;
    }

    /**
     * @return string|null
     */
    public function getAvsMessage(): ?string
    {
        return $this->avsMessage;
    }

    /**
     * @return string|null
     */
    public function getCvvMessage(): ?string
    {
        return $this->cvvMessage;
    }

    /**
     * @return string|null
     */
    public function getTransactionMessage(): ?string
    {
        return $this->transactionMessage;
    }

    /**
     * @return string|null
     */
    public function getProcessorId(): ?string
    {
        return $this->processorId;
    }

    /**
     * @return bool|null
     */
    public function wasSuccessful(): ?bool
    {
        return $this->successful;
    }
    
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            // Pulled from actual response
            'raw_response'           => $this->rawResponse,
            'response'               => $this->response,
            'responsetext'           => $this->responseText,
            'auth_code'              => $this->authCode,
            'transactionid'          => $this->transactionId,
            'avsresponse'            => $this->avsResponse,
            'cvvresponse'            => $this->cvvResponse,
            'orderid'                => $this->orderId,
            'customer_vault_id'      => $this->customerVaultId,
            'cc_number'              => $this->ccNumber,
            'checkaba'               => $this->checkAba,
            'checkaccount'           => $this->checkAccount,
            'type'                   => $this->transactionType,
            'response_code'          => $this->responseCode,
            'emv_auth_response_data' => $this->emvAuthResponseData,

            // Generated based on data from response
            'avs_exact_match'        => $this->avsExactMatch,
            'avs_unavailable'        => $this->avsUnavailable,
            'cvv_match'              => $this->cvvMatch,
            'cvv_unavailable'        => $this->cvvUnavailable,
            'avs_partial_match'      => $this->avsPartialMatch,
            'avs_message'            => $this->avsMessage,
            'cvv_message'            => $this->cvvMessage,
            'transaction_message'    => $this->transactionMessage,
            'successful'             => $this->successful,
        ];
    }

    /**
     * Set the current object's properties from an array;
     *
     * @param array $data
     * @return NmiResponseProvider
     */
    public function fromArray(array $data = []): NmiResponseProvider
    {
        $this->response            = $data['response']               ?? null;
        $this->responseText        = $data['responsetext']           ?? null;
        $this->authCode            = $data['authcode']               ?? null;
        $this->transactionId       = $data['transactionid']          ?? null;
        $this->avsResponse         = $data['avsresponse']            ?? null;
        $this->cvvResponse         = $data['cvvresponse']            ?? null;
        $this->orderId             = $data['orderid']                ?? null;
        $this->customerVaultId     = $data['customer_vault_id']      ?? null;
        $this->ccNumber            = $data['cc_number']              ?? null;
        $this->checkAba            = $data['checkaba']               ?? null;
        $this->checkAccount        = $data['checkaccount']           ?? null;
        $this->transactionType     = $data['type']                   ?? null;
        $this->responseCode        = $data['response_code']          ?? null;
        $this->emvAuthResponseData = $data['emv_auth_response_data'] ?? null;
        $this->processorId         = $data['processor_id']           ?? null;

        return $this;
    }

    /**
     * Parse the NMI API response and fill this object's properties from the returned data
     *
     * @param string $rawResponse
     * @return NmiResponseProvider
     */
    public function parseResponse(string $rawResponse): NmiResponseProvider
    {
        $this->rawResponse = $rawResponse;
        parse_str($this->rawResponse , $responseParams);
        if ($responseParams) {
            $this->fromArray($responseParams);
        }
        if ($this->avsResponse) {
            $this->avsExactMatch    = $this->isExactAvsMatch($this->avsResponse);
            $this->avsPartialMatch  = $this->isPartialAvsMatch($this->avsResponse);
            $this->avsUnavailable   = $this->isAvsUnavailable($this->avsResponse);
            $this->avsMessage       = $this->getAvsResponseCodeText($this->avsResponse);
        }
        if ($this->cvvResponse) {
            $this->cvvMatch       = $this->isCvvMatch($this->cvvResponse);
            $this->cvvUnavailable = $this->isCvvUnavailable($this->cvvResponse);
            $this->cvvMessage     = $this->getCvvResponseCodeText($this->cvvResponse);
        }
        if ($this->response) {
            $this->successful = $this->response === self::RESPONSE_STATUS_APPROVED;
        }
        if ($this->responseCode) {
            $this->transactionMessage = $this->getResponseCodeText($this->responseCode);
        }
        return $this;
    }

}