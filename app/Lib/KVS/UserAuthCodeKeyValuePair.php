<?php

namespace App\Lib\KVS;

class UserAuthCodeKeyValuePair extends KeyValuePair
{
    const SCOPE       = 'USER_AUTH_CODE';
    const EXPIRES     = 300;
    const CODE_LENGTH = 5;

    /**
     * @return string
     */
    protected function generate_key()
    {
        $min  = pow(10, self::CODE_LENGTH - 1);
        $max  = $min * 10 - 1;
        $code = mt_rand($min, $max);

        return $code;
    }
}
