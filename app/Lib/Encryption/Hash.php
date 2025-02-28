<?php

namespace App\Lib\Encryption;

use App\Exceptions\UndefinedEncryptionSaltException;
use App\Exceptions\InvalidEncryptionAlgorithmException;
use App\Exceptions\UndefinedSuffleSeedException;

/**
 * Class Hash
 * @package App\Lib\Encryption
 */
class Hash
{

    /**
     * @var string|null
     */
   protected $raw;

    /**
     * @var string|null
     */
   protected $salt;

    /**
     * @var string
     */
   protected $saltTemplate = '$1$%s$';

    /**
     * @var int
     */
   protected $hashType = CRYPT_MD5;

    /**
     * Hash constructor.
     * @param $str
     */
   public function __construct($str)
   {
       $this->raw = $str;
   }

    /**
     * @param $str
     * @return string
     */
   public static function hash($str)
   {
      return (string) (new static($str));
   }

    /**
     * @return string|null
     * @throws InvalidEncryptionAlgorithmException
     * @throws UndefinedEncryptionSaltException
     * @throws UndefinedSuffleSeedException
     */
   protected function getHash()
   {
      return crypt($this->shuffle($this->raw), $this->salt());
   }

    /**
     * @return mixed
     * @throws UndefinedEncryptionSaltException
     */
   protected function getSalt()
   {
       if (! $this->salt) {
          throw new UndefinedEncryptionSaltException;
       }

       return $this->salt;
   }

    /**
     * @return int
     */
   protected function getShuffleSeed()
   {
       return mt_rand(1000, 9999);
   }

    /**
     * @return string
     * @throws InvalidEncryptionAlgorithmException
     * @throws UndefinedEncryptionSaltException
     */
   protected function salt()
   {
       if ($this->hashType === 1) {
           return sprintf($this->saltTemplate, $this->getSalt());
       }

       throw new InvalidEncryptionAlgorithmException;
   }

    /**
     * @param $str
     * @return string
     * @throws UndefinedSuffleSeedException
     */
   protected function shuffle($str)
   {
       if (! ($seed = $this->getShuffleSeed())) {
           throw new UndefinedSuffleSeedException;
       }

       srand($seed);
       $shuffled = str_shuffle($str);
       srand();

       return $shuffled;
   }

    /**
     * @param $compare
     * @return bool
     * @throws InvalidEncryptionAlgorithmException
     * @throws UndefinedEncryptionSaltException
     * @throws UndefinedSuffleSeedException
     */
   public function matches($compare)
   {
       return hash_equals($this->getHash(), $compare);
   }

    /**
     * @return string|null
     * @throws InvalidEncryptionAlgorithmException
     * @throws UndefinedEncryptionSaltException
     * @throws UndefinedSuffleSeedException
     */
   public function __toString()
   {
       return $this->getHash();
   }
}
