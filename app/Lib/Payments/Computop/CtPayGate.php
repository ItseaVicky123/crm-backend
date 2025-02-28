<?php

namespace App\Lib\Payments\Computop;

class CtPayGate
{
    use CtBlowfish;

    /**
     * @param        $arText
     * @param        $sSplit
     * @param string $sArg
     * @return mixed|string
     */
    public function ctSplit($arText, $sSplit, $sArg = "")
    {
        $b    = '';
        $i    = 0;
        $info = '';

        while($i < count ($arText)) {
            $b = explode($sSplit, $arText [$i++]);
            if ($b[0] === $sArg) {
                $info = $b[1];
                $b[0] = 0;
                break;

            }
        }

        if (($sArg !== '') & ($b[0] !== 0)) {
            $info = '';
        }

        return $info;
    }

    /* prepare notify parameters
        @param array parameters
        @param string delimiter
        @return string logfile content
        @access public */
    public function ctNotify($arText, $sSplit): string
    {
        $i    = 0;
        $info = '';

        while ($i < count ($arText)) {
            $b = explode($sSplit, $arText [$i++]);
            $info .= "\n".$b[0].":\t".$b[1];
        }

        return $info;
    }

    /**
     * @param string $response
     * @return array
     */
    public function convertToArray($response): array
    {
        $arrayResult = [];
        $exploded = explode('&', $response);

        foreach ($exploded as $keyValue) {
            $explodedItems                  = explode('=', $keyValue);
            $arrayResult[$explodedItems[0]] = $explodedItems[1];
        }

        return $arrayResult;
    }

    /**
     * Calculate the MAC value.
     *
     * @param string $PayId
     * @param string $TransID
     * @param string $MerchantID
     * @param integer $Amount
     * @param string $Currency
     * @param string $HmacPassword
     * @return string
     */
    public function ctHMAC(string $PayId = '', string $TransID = '', string $MerchantID, int $Amount, string $Currency, string $HmacPassword): string
    {
        return hash_hmac('sha256', "$PayId*$TransID*$MerchantID*$Amount*$Currency", $HmacPassword);
    }
}
