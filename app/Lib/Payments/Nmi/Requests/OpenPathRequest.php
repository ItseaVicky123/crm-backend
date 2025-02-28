<?php

namespace App\Lib\Payments\Nmi\Requests;

use App\Lib\Payments\Nmi\Classes\{NmiRequest, NmiResponse};
use App\Lib\Payments\Nmi\Interfaces\NmiResponseProvider;

class OpenPathRequest extends NmiRequest
{
    /**
     * @inheritDoc
     */
    public function process(string $transactionType): NmiResponseProvider
    {
        $this->transactionType = $transactionType;
        $payload               = $this->toArray();

        // Modify the CVV with leading zeros (see DEV-5334)
        // It "falls through" the padding if it's already the appropriate length
        if (isset($payload['cvv'])) {
            $isAmex = (strpos($payload['ccnumber'], '3') === 0 && strlen($payload['ccnumber']) === 15);

            $padLength      = $isAmex ? 4 : 3;
            $payload['cvv'] = str_pad($payload['cvv'], $padLength, '0', STR_PAD_LEFT);
        }

        $response = $this->post(null, $payload);
        return $this->nmiResponse = (new NmiResponse())->parseResponse($response);
    }
}
