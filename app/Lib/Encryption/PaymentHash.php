<?php

namespace App\Lib\Encryption;

/**
 * Class PaymentHash
 * @package App\Lib\Encryption
 */
class PaymentHash extends Sha512Hash
{
    /**
     * @return mixed
     * @throws \App\Exceptions\UndefinedEncryptionSaltException
     */
    protected function getSalt()
    {
       $this->salt = defined('CRM_SALT') ? CRM_SALT : null;

       return parent::getSalt();
    }

    /**
     * @return int|null
     */
    protected function getShuffleSeed()
    {
       return defined('CRM_SEED') ? CRM_SEED : null;
    }
}
