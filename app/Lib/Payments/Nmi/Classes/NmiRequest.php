<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Traits\TransactionType;
use App\Traits\ExternalAPIHelper;
use App\Lib\Payments\Nmi\Interfaces\TdsInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\NmiOrderProvider;
use App\Lib\Payments\Nmi\Interfaces\VaultInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\VatTaxInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\NmiRequestProvider;
use App\Lib\Payments\Nmi\Interfaces\BillingInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\NmiResponseProvider;
use App\Lib\Payments\Nmi\Interfaces\ShippingInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\RecurringInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\PaymentMethodProvider;
use App\Lib\Payments\Nmi\Interfaces\DescriptorInfoProvider;
use App\Lib\Payments\Nmi\Interfaces\MerchantFieldsProvider;
use Illuminate\Contracts\Support\Arrayable;

class NmiRequest implements NmiRequestProvider
{
    use ExternalAPIHelper,
        TransactionType;

    public const API_URL = 'secure.networkmerchants.com/api/transact.php';

    /**
     * Transaction that is authorized by the bank and immediately flagged for settlement.
     */
    public const SALE_TRANSACTION = 'sale';

    /**
     * Transaction that has been authorized by the bank and has not been flagged for settlement.
     */
    public const AUTH_TRANSACTION = 'auth';

    /**
     * Transaction that processes a credit to an arbitrary CC
     */
    public const CREDIT_TRANSACTION = 'credit';

    /**
     * Transaction to validate a CC without applying an authorization.
     * NOTE: amount must be omitted or set to 0.00
     */
    public const VALIDATE_TRANSACTION = 'validate';

    /**
     * Transaction where a merchant calls MC or Visa to get an authorization code and
     * then submits it through the gateway for settlement.
     */
    public const OFFLINE_SALE_TRANSACTION = 'offline';

    /**
     * Transaction to mark an authorization for settlement.
     */
    public const CAPTURE_TRANSACTION = 'capture';

    /**
     * Transaction to partially or fully refund a previously settled transaction.
     */
    public const REFUND_TRANSACTION = 'refund';

    /**
     * Transaction that will cancel a sale, authorization, or captured authorization
     * if performed before it has settled
     */
    public const VOID_TRANSACTION = 'void';

    /**
     * Transaction to update the updatable fields of a previously submitted transaction.
     */
    public const UPDATE_TRANSACTION = 'update';

    /**
     * The Payment Gateway Demo Account can be used for testing at any time.
     * Please use the below security key for testing with this account.
     * This account is always available and allows testing in a completely sandboxed environment.
     * Like all testing methods, no card or check data will ever be sent for actual processing.
     */
    public const TEST_MODE_SECURITY_KEY = '6457Thfj624V5r7WUwc5v6a68Zsd6YEm';
    public const TEST_MODE_USERNAME     = 'demo';
    public const TEST_MODE_PASSWORD     = 'password';
    public const TEST_MODE_ENABLED      = 'enabled';

    /**
     * NMI API Security Key assigned to a merchant account.
     * New keys can be generated from the NMI merchant control panel in Settings > Security Keys
     *
     * @var string|null
     */
    protected ?string $securityKey = null;

    /**
     * @var string|null
     */
    protected ?string $username = null;

    /**
     * @var string|null
     */
    protected ?string $password = null;

    /**
     * If set to TEST_MODE_ENABLED and providing one of the PaymentMethod::TEST_CC_NUMBER_X credit card numbers
     * with PaymentMethod::TEST_MODE_CC_EXP as the expiration date, the single transaction will process in test mode.
     *
     * To see this transaction in reporting, you will need to toggle your account to test mode,
     * but the Direct Post API testing can be done without doing this.
     *
     * @var string|null
     */
    protected ?string $testMode = null;

    /**
     * Sets the time in seconds for duplicate transaction checking on supported processors.
     * Set to 0 to disable duplicate checking.
     * This value should not exceed 7862400.
     *
     * @var int|null
     */
    protected ?int $dupSeconds = null;

    /**
     * @var NmiOrderProvider|null
     */
    protected ?NmiOrderProvider $nmiOrder = null;

    /**
     * @var BillingInfoProvider|null
     */
    protected ?BillingInfoProvider $billingInfo = null;

    /**
     * @var ShippingInfoProvider|null
     */
    protected ?ShippingInfoProvider $shippingInfo = null;

    /**
     * @var DescriptorInfoProvider|null
     */
    protected ?DescriptorInfoProvider $descriptorInfo = null;

    /**
     * @var TdsInfoProvider|null
     */
    protected ?TdsInfoProvider $tdsInfo = null;

    /**
     * @var VatTaxInfoProvider|null
     */
    protected ?VatTaxInfoProvider $vatTaxInfo = null;

    /**
     * @var RecurringInfoProvider|null
     */
    protected ?RecurringInfoProvider $recurringInfo = null;

    /**
     * @var PaymentMethodProvider|null
     */
    protected ?PaymentMethodProvider $paymentMethod = null;

    /**
     * @var VaultInfoProvider|null
     */
    protected ?VaultInfoProvider $vaultInfo = null;

    /**
     * @var MerchantFieldsProvider|null
     */
    protected ?MerchantFieldsProvider $merchantFields = null;

    /**
     * @var NmiResponseProvider|null
     */
    protected ?NmiResponseProvider $nmiResponse = null;

    /**
     * NmiRequest constructor.
     *
     * @param string|null $apiUrl
     * @param string|null $transactionType
     */
    public function __construct(?string $apiUrl = self::API_URL, ?string $transactionType = null)
    {
        $this->setSendType('form_params');
        $this->setUriDomain($apiUrl);
        $this->setObfuscationKeys([
            'security_key',
            'social_security_number',
            'drivers_license_number',
            'drivers_license_dob',
            'signature_image',
            'payment_token',
            'ccnumber',
            'ccexp',
            'cvv',
            'checkaba',
            'checkaccount',
            'authorization_code',
            'authorizationcode',
            'cardholder_auth',
            'cavv',
            'xid',
            'customer_vault_id',
        ]);
        if ($transactionType) {
            $this->setTransactionType($transactionType);
        }
    }

    /**
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
     * @param string|null $transactionType
     * @return NmiRequestProvider
     */
    public function setTransactionType(?string $transactionType): NmiRequestProvider
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecurityKey(): ?string
    {
        return $this->securityKey;
    }

    /**
     * @param string|null $securityKey
     * @return NmiRequestProvider
     */
    public function setSecurityKey(?string $securityKey): NmiRequestProvider
    {
        $this->securityKey = $securityKey;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDupSeconds(): ?int
    {
        return $this->dupSeconds;
    }

    /**
     * @param int|null $dup_seconds
     * @return NmiRequestProvider
     */
    public function setDupSeconds(?int $dup_seconds): NmiRequestProvider
    {
        $this->dupSeconds = $dup_seconds;

        return $this;
    }

    /**
     * @return NmiOrderProvider|null
     */
    public function getNmiOrder(): ?NmiOrderProvider
    {
        return $this->nmiOrder;
    }

    /**
     * @param NmiOrderProvider|null $nmiOrder
     * @return NmiRequestProvider
     */
    public function setNmiOrder(?NmiOrderProvider $nmiOrder): NmiRequestProvider
    {
        $this->nmiOrder = $nmiOrder;

        return $this;
    }

    /**
     * @return BillingInfoProvider|null
     */
    public function getBillingInfo(): ?BillingInfoProvider
    {
        return $this->billingInfo;
    }

    /**
     * @param BillingInfoProvider|null $billingInfo
     * @return NmiRequestProvider
     */
    public function setBillingInfo(?BillingInfoProvider $billingInfo): NmiRequestProvider
    {
        $this->billingInfo = $billingInfo;

        return $this;
    }

    /**
     * @return ShippingInfoProvider|null
     */
    public function getShippingInfo(): ?ShippingInfoProvider
    {
        return $this->shippingInfo;
    }

    /**
     * @param ShippingInfoProvider|null $shippingInfo
     * @return NmiRequestProvider
     */
    public function setShippingInfo(?ShippingInfoProvider $shippingInfo): NmiRequestProvider
    {
        $this->shippingInfo = $shippingInfo;

        return $this;
    }

    /**
     * @return DescriptorInfoProvider|null
     */
    public function getDescriptorInfo(): ?DescriptorInfoProvider
    {
        return $this->descriptorInfo;
    }

    /**
     * @param DescriptorInfoProvider|null $descriptorInfo
     * @return NmiRequestProvider
     */
    public function setDescriptorInfo(?DescriptorInfoProvider $descriptorInfo): NmiRequestProvider
    {
        $this->descriptorInfo = $descriptorInfo;

        return $this;
    }

    /**
     * @return TdsInfoProvider|null
     */
    public function getTdsInfo(): ?TdsInfoProvider
    {
        return $this->tdsInfo;
    }

    /**
     * @param TdsInfoProvider|null $tdsInfo
     * @return NmiRequestProvider
     */
    public function setTdsInfo(?TdsInfoProvider $tdsInfo): NmiRequestProvider
    {
        $this->tdsInfo = $tdsInfo;

        return $this;
    }

    /**
     * @return VatTaxInfoProvider|null
     */
    public function getVatTaxInfo(): ?VatTaxInfoProvider
    {
        return $this->vatTaxInfo;
    }

    /**
     * @param VatTaxInfoProvider|null $vatTaxInfo
     * @return NmiRequestProvider
     */
    public function setVatTaxInfo(?VatTaxInfoProvider $vatTaxInfo): NmiRequestProvider
    {
        $this->vatTaxInfo = $vatTaxInfo;

        return $this;
    }

    /**
     * @return RecurringInfoProvider|null
     */
    public function getRecurringInfo(): ?RecurringInfoProvider
    {
        return $this->recurringInfo;
    }

    /**
     * @param RecurringInfoProvider|null $recurringInfo
     * @return NmiRequestProvider
     */
    public function setRecurringInfo(?RecurringInfoProvider $recurringInfo): NmiRequestProvider
    {
        $this->recurringInfo = $recurringInfo;

        return $this;
    }

    /**
     * @return PaymentMethodProvider|null
     */
    public function getPaymentMethod(): ?PaymentMethodProvider
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethodProvider|null $paymentMethod
     * @return NmiRequestProvider
     */
    public function setPaymentMethod(?PaymentMethodProvider $paymentMethod): NmiRequestProvider
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return VaultInfoProvider|null
     */
    public function getVaultInfo(): ?VaultInfoProvider
    {
        return $this->vaultInfo;
    }

    /**
     * @param VaultInfoProvider|null $vaultInfo
     * @return NmiRequestProvider
     */
    public function setVaultInfo(?VaultInfoProvider $vaultInfo): NmiRequestProvider
    {
        $this->vaultInfo = $vaultInfo;

        return $this;
    }

    /**
     * @return MerchantFieldsProvider|null
     */
    public function getMerchantFields(): ?MerchantFieldsProvider
    {
        return $this->merchantFields;
    }

    /**
     * @param MerchantFieldsProvider|null $merchantFields
     * @return NmiRequestProvider
     */
    public function setMerchantFields(?MerchantFieldsProvider $merchantFields): NmiRequestProvider
    {
        $this->merchantFields = $merchantFields;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return NmiRequestProvider
     */
    public function setUsername(?string $username): NmiRequestProvider
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return NmiRequestProvider
     */
    public function setPassword(?string $password): NmiRequestProvider
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function isTestMode(): ?string
    {
        return $this->testMode === self::TEST_MODE_ENABLED;
    }

    /**
     * @return string|null
     */
    public function getTestMode(): ?string
    {
        return $this->testMode;
    }

    /**
     * @param bool $setKey True if we should set the Test mode security key also
     * @param bool $setUsername True if we should set the Test mode username also
     * @param bool $setPassword True if we should set the Test mode password also
     * @return NmiRequestProvider
     */
    public function setTestMode(bool $setKey = true, bool $setUsername = false, bool $setPassword = false): NmiRequestProvider
    {
        if ($setKey) {
            $this->securityKey = self::TEST_MODE_SECURITY_KEY;
        }
        if ($setUsername) {
            $this->username = self::TEST_MODE_USERNAME;
        }
        if ($setPassword) {
            $this->password = self::TEST_MODE_PASSWORD;
        }
        $this->testMode = self::TEST_MODE_ENABLED;

        return $this;
    }

    /**
     * @return NmiResponseProvider
     */
    public function getNmiResponse(): NmiResponseProvider
    {
        return $this->nmiResponse;
    }

    /**
     * @param NmiResponseProvider $nmiResponse
     * @return NmiRequestProvider
     */
    public function setNmiResponse(NmiResponseProvider $nmiResponse): NmiRequestProvider
    {
        $this->nmiResponse = $nmiResponse;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'type'         => $this->transactionType,
            'security_key' => $this->securityKey,
            'username'     => $this->username,
            'password'     => $this->password,
            'test_mode'    => $this->testMode,
            'dup_seconds'  => $this->dupSeconds,
        ];

        if ($this->nmiOrder instanceof Arrayable) {
            $data = array_merge($data, $this->nmiOrder->toArray());
        }

        if ($this->billingInfo instanceof Arrayable) {
            $data = array_merge($data, $this->billingInfo->toArray());
        }

        if ($this->shippingInfo instanceof Arrayable) {
            $data = array_merge($data, $this->shippingInfo->toArray());
        }

        if ($this->descriptorInfo instanceof Arrayable) {
            $data = array_merge($data, $this->descriptorInfo->toArray());
        }

        if ($this->tdsInfo instanceof Arrayable) {
            $data = array_merge($data, $this->tdsInfo->toArray());
        }

        if ($this->vatTaxInfo instanceof Arrayable) {
            $data = array_merge($data, $this->vatTaxInfo->toArray());
        }

        if ($this->recurringInfo instanceof Arrayable) {
            $data = array_merge($data, $this->recurringInfo->toArray());
        }

        if ($this->paymentMethod instanceof Arrayable) {
            $data = array_merge($data, $this->paymentMethod->toArray());
        }

        if ($this->vaultInfo instanceof Arrayable) {
            $data = array_merge($data, $this->vaultInfo->toArray());
        }

        if ($this->merchantFields instanceof Arrayable) {
            $data = array_merge($data, $this->merchantFields->toArray());
        }

        return array_filter($data);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processSale(): NmiResponseProvider
    {
        return $this->process(self::SALE_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processOfflineSale(): NmiResponseProvider
    {
        return $this->process(self::OFFLINE_SALE_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processCredit(): NmiResponseProvider
    {
        return $this->process(self::CREDIT_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processAuth(): NmiResponseProvider
    {
        return $this->process(self::AUTH_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processValidate(): NmiResponseProvider
    {
        return $this->process(self::VALIDATE_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processCapture(): NmiResponseProvider
    {
        return $this->process(self::CAPTURE_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processRefund(): NmiResponseProvider
    {
        return $this->process(self::REFUND_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processVoid(): NmiResponseProvider
    {
        return $this->process(self::VOID_TRANSACTION);
    }

    /**
     * @return NmiResponseProvider
     */
    public function processUpdate(): NmiResponseProvider
    {
        return $this->process(self::UPDATE_TRANSACTION);
    }

    /**
     * Process an NMI transaction
     *
     * @param string $transactionType One of the X_TRANSACTION constants;
     * @return NmiResponseProvider
     */
    public function process(string $transactionType): NmiResponseProvider
    {
        $this->transactionType = $transactionType;
        $payload               = $this->toArray();
        $response              = $this->post(null, $payload);
        return $this->nmiResponse = (new NmiResponse())->parseResponse($response);
    }

}
