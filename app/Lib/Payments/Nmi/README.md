## NMI [Direct Post](https://secure.networkmerchants.com/gw/merchants/resources/integration/integration_portal.php) Transactions SDK


- [TODO](#todo)
- [Examples](#examples)
    + [Shared Test Variables](#shared-test-variables)
  * [Validate CC](#validate-cc)
  * [Authorize](#authorize)
  * [Capture](#capture)
  * [Straight Sale](#straight-sale)
  * [Refund](#refund)
  * [Update](#update)
  * [Offline Sale](#offline-sale)
  * [Credit](#credit)

<a name="todo"></a>
### TODO
- Add Unit tests

### Examples

##### Shared Test Variables

[Variable Reference](https://secure.networkmerchants.com/gw/merchants/resources/integration/integration_portal.php#transaction_variables)

```
$address1       = BillingInfo::TEST_MODE_ADDRESS1;
$address2       = 'Apt B';
$country        = 'US';
$state          = 'FL';
$city           = "Panama City Beach";
$zip            = BillingInfo::TEST_MODE_ZIP;
$company        = 'Sticky.io';
$email          = 'support@sticky.io';
$fax            = '3345551234';
$phone          = '3345551234';
$firstName      = 'John';
$lastName       = 'Smith';
$website        = 'https://Sticky.io';
$ccExp          = PaymentMethod::TEST_MODE_CC_EXP;
$ccNum          = PaymentMethod::TEST_MODE_CC_NUMBER_VISA;
$ccCvv          = PaymentMethod::TEST_MODE_CVV;
$amount         = '100.10';
$tax            = '5.00';
$orderId        = '1111111111';
$merchantField0 = 'xxxxxxxxxx';
$merchantField1 = 'yyyyyyyyyy';
$productCode1   = '1111111111';
$productDesc1   = 'Product one';
$productCode2   = '2222222222';
$productDesc2   = 'Product two';
$securityKey    = NmiRequest::TEST_MODE_SECURITY_KEY;
```

--- 
<a name="validate-cc"></a>
#### Validate CC

> Transaction to validate a CC without applying an authorization. 
> NOTE: amount must be omitted or set to 0.00

```
$billingInfo = (new BillingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setFax($fax)
    ->setPhone($phone)
    ->setWebsite($website)
    ->setTax($tax)
;

$paymentMethod = (new PaymentMethod())
    ->setCcExp($ccExp)
    ->setCcnumber($ccNum)
    ->setCvv($ccCvv)
;

$nmiOrder = (new NmiOrder())
    ->setOrderid($orderId)
;
$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processValidate();
$responseData = $nmiResponse->toArray();
```

--- 
#### Authorize

> Transaction that has been authorized by the bank and has not been flagged for settlement.

```
$billingInfo = (new BillingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setFax($fax)
    ->setPhone($phone)
    ->setWebsite($website)
    ->setAmount($amount)
    ->setTax($tax)
;

$shippingInfo = (new ShippingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setCarrier($carrier)
;

$paymentMethod = (new PaymentMethod())
    ->setCcExp($ccExp)
    ->setCcnumber($ccNum)
    ->setCvv($ccCvv)
;

$nmiOrderItem1 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc1)
    ->setItemProductCode($productCode1)
;

$nmiOrderItem2 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc2)
    ->setItemProductCode($productCode2)
;

$nmiOrder = (new NmiOrder())
    ->setOrderid($orderId)
    ->setItems([
        $nmiOrderItem1,
        $nmiOrderItem2
    ])
;

$merchantFields = (new MerchantFields())
    ->setField(0, $merchantField0)
    ->setField(1, $merchantField1)
;

$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setShippingInfo($shippingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setMerchantFields($merchantFields)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processAuth();
$responseData = $nmiResponse->toArray();

$responseAuthCode      = $responseData['authcode'] ?: '';
$responseTransactionId = $responseData['transactionid'] ?: '';

$this->response($nmiResponse->toArray());
```

--- 
#### Capture

> Transaction to mark an authorization for settlement.

```
$billingInfo = (new BillingInfo())
    ->setAmount($amount)
;

$paymentMethod = (new PaymentMethod())
    ->setTransactionid($responseTransactionId)
    ->setAuthorizationCode($responseAuthCode)
;
$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processCapture();
$responseData = $nmiResponse->toArray();

$this->response($nmiResponse->toArray());
```


--- 
#### Straight Sale

> Transaction that is authorized by the bank and immediately flagged for settlement

```
$billingInfo = (new BillingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setFax($fax)
    ->setPhone($phone)
    ->setWebsite($website)
    ->setAmount($amount)
;

$shippingInfo = (new ShippingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
;

$paymentMethod = (new PaymentMethod())
    ->setCcExp($ccExp)
    ->setCcnumber($ccNum)
    ->setCvv($ccCvv)
;

$nmiOrderItem1 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc1)
    ->setItemProductCode($productCode1)
;

$nmiOrderItem2 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc2)
    ->setItemProductCode($productCode2)
;

$nmiOrder = (new NmiOrder())
    ->setOrderid($orderId)
    ->setItems([
        $nmiOrderItem1,
        $nmiOrderItem2
    ])
;

$merchantFields = (new MerchantFields())
    ->setField(0, $merchantField0)
    ->setField(1, $merchantField1)
;

$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setShippingInfo($shippingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setMerchantFields($merchantFields)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processSale();
$responseData = $nmiResponse->toArray();

```

---
#### Refund

> Transaction to partially or fully refund a previously settled transaction.

```
$responseTransactionId = $responseData['transactionid'] ?: '';

$voidReason    = PaymentMethod::VOID_REASON_USER_CANCEL;
$refundAmount  = '5.00'; // NOTE: setting this amount to 0.00 will refund the entire original amount.

$billingInfo = (new BillingInfo())
    ->setAmount($refundAmount)
;

$paymentMethod = (new PaymentMethod())
    ->setTransactionid($responseTransactionId)
    ->setVoidReason($voidReason)
;
$nmiRequest = (new NmiRequest())
    ->setPaymentMethod($paymentMethod)
    ->setBillingInfo($billingInfo)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processRefund();
$responseData = $nmiResponse->toArray();
```


--- 
#### Update

> Transaction to update the updatable fields of a previously submitted transaction.

```
$transactionId     = '22222222'; // Use a valid TID here from a previous request
$newDescription    = 'New description';
$newTax            = '4.00';
$newMerchantField0 = 'Updated merchant field 0';
$merchantField3    = 'New merchant field 1';

$billingInfo = (new BillingInfo())
    ->setTax($newTax)
;

$shippingInfo = (new ShippingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCarrier($carrier)
;

$paymentMethod = (new PaymentMethod())
    ->setTransactionid($transactionId)
;

$nmiOrder = (new NmiOrder())
    ->setOrderDescription($newDescription)
;

$merchantFields = (new MerchantFields())
    ->setField(0, $newMerchantField0)
    ->setField(3, $merchantField3)
;

$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setShippingInfo($shippingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setMerchantFields($merchantFields)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processUpdate();
$responseData = $nmiResponse->toArray();
```


--- 
#### Offline Sale
> Transaction where a merchant calls MC or Visa to get an authorization code and then submits it through the gateway for settlement.

```
$authCode = '1DKDK3';

$billingInfo = (new BillingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setFax($fax)
    ->setPhone($phone)
    ->setWebsite($website)
    ->setAmount($amount)
    ->setTax($tax)
;

$shippingInfo = (new ShippingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setCarrier($carrier)
;

$paymentMethod = (new PaymentMethod())
    ->setCcExp($ccExp)
    ->setCcnumber($ccNum)
    ->setCvv($ccCvv)
    ->setAuthorizationCode($authCode)
;

$nmiOrderItem1 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc1)
    ->setItemProductCode($productCode1)
;

$nmiOrderItem2 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc2)
    ->setItemProductCode($productCode2)
;

$nmiOrder = (new NmiOrder())
    ->setOrderid($orderId)
    ->setItems([
        $nmiOrderItem1,
        $nmiOrderItem2
    ])
;

$merchantFields = (new MerchantFields())
    ->setField(0, $merchantField0)
    ->setField(1, $merchantField1)
;

$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setShippingInfo($shippingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setMerchantFields($merchantFields)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processOfflineSale();
$responseData = $nmiResponse->toArray();
```


---
#### Credit 

> Transaction that processes a credit to an arbitrary CC

```
 $billingInfo = (new BillingInfo())
    ->setAddress1($address1)
    ->setAddress2($address2)
    ->setCountry($country)
    ->setState($state)
    ->setCity($city)
    ->setZip($zip)
    ->setCompany($company)
    ->setEmail($email)
    ->setFirstname($firstName)
    ->setLastname($lastName)
    ->setFax($fax)
    ->setPhone($phone)
    ->setWebsite($website)
    ->setAmount($amount)
    ->setTax($tax)
;

$paymentMethod = (new PaymentMethod())
    ->setCcExp($ccExp)
    ->setCcnumber($ccNum)
    ->setCvv($ccCvv)
;

$nmiOrderItem1 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc1)
    ->setItemProductCode($productCode1)
;

$nmiOrderItem2 = (new NmiOrderItem())
    ->setItemQuantity(1)
    ->setItemDescription($productDesc2)
    ->setItemProductCode($productCode2)
;

$nmiOrder = (new NmiOrder())
    ->setOrderid($orderId)
    ->setItems([
        $nmiOrderItem1,
        $nmiOrderItem2
    ])
;

$nmiRequest = (new NmiRequest())
    ->setBillingInfo($billingInfo)
    ->setPaymentMethod($paymentMethod)
    ->setNmiOrder($nmiOrder)
    ->setTestMode()  // use `->setSecurityKey($securityKey)` in production
;

$nmiResponse = $nmiRequest->processCredit();
$responseData = $nmiResponse->toArray();
```