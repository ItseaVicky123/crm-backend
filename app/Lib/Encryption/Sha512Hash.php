<?php

namespace App\Lib\Encryption;

/**
 * Class Sha512Hash
 * @package App\Lib\Encryption
 */
class Sha512Hash extends Hash
{

    /**
     * @var string
     */
    protected $saltTemplate = '$6$rounds=5000$%s$';

    /**
     * @var int
     */
    protected $hashType = CRYPT_SHA512;
}
