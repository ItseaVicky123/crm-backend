<?php

namespace App\Lib\KVS;

class PaymentTokenKeyValuePair extends KeyValuePair
{
    const SCOPE   = 'PAYMENT_TOKEN';
    const EXPIRES = 900;
}
