<?php

namespace App\Lib\Encryption;

use Illuminate\Support\Facades\Crypt;

/**
 * Class System
 * @package App\Lib\Encryption
 */
class System
{

    /**
     * @var string
     */
    protected static $pattern = '~_lum(.*)~';

    /**
     * @var string
     */
    protected static $prepend = '_lum';

    /**
     * @param $data
     * @return string
     */
    public static function encrypt($data)
    {
        return self::$prepend . Crypt::encrypt($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function decrypt($data)
    {
        if (preg_match(self::$pattern, $data, $matches)) {
            return Crypt::decrypt($matches[1]);
        }

        return \system_security::decrypt($data);
    }
}
