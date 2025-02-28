<?php

namespace App\Lib;

use App\Exceptions\UnknownCardBrandException;

class CreditCard
{
    const VISA     = 'visa';
    const MASTER   = 'master';
    const DISCOVER = 'discover';
    const AMEX     = 'amex';
    const DINERS   = 'diners';

    private $number = '';
    private $cvv;
    private $expiry;
    private $brand;

    /**
     * CreditCard constructor.
     * @param string|null $number
     * @param string|null $brand
     * @throws UnknownCardBrandException
     */
    public function __construct(string $number = null, string $brand = null)
    {
        if ($number) {
            $this->number = $number;

            $this->brand = $brand ?? $this->getBrand();
        }
    }

    /**
     * @param string|null $number
     * @param string|null $brand
     * @return CreditCard
     * @throws UnknownCardBrandException
     */
    public static function make(string $number = null, string $brand = null)
    {
        return new static($number, $brand);
    }

    /**
     * @return string cvv
     */
    public function cvv()
    {
        return $this->cvv;
    }

    /**
     * @return string expiry
     */
    public function expiry()
    {
        return $this->expiry;
    }

    /**
     * @return string expiry
     */
    public function expiryMonth()
    {
        return substr($this->expiry, 0, 2);
    }

    /**
     * @return string expiry
     */
    public function expiryYear()
    {
        return substr($this->expiry, -2);
    }

    /**
     * @return string expiryShort
     */
    public function expiryShort()
    {
        return $this->expiryMonth() . substr($this->expiryYear(), -2);
    }

    /**
     * @return string
     */
    public function brand()
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function encrypt()
    {
        return \payment_source::encrypt_credit_card($this->number);
    }

    /**
     * @return integer
     */
    public function first_six()
    {
        return substr($this->number, 0, 6);
    }

    /**
     * @return integer
     */
    public function last_four()
    {
        return substr($this->number, -4);
    }

    /**
     * @param $prop
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function set($prop, $value = null)
    {
        if (is_array($prop)) {
            foreach ($prop as $p => $v) {
                $this->_set($p, $v);
            }
        } else {
            $this->_set($prop, $value);
        }

        return $this;
    }

    /**
     * @param string $prop
     * @param $value
     * @throws \Exception
     */
    private function _set(string $prop, $value)
    {
        if (! property_exists($this, $prop)) {
            throw new \Exception("Invalid property '{$prop}'");
        }

        $this->$prop = $value;
    }

    /**
     * @return string
     * @throws UnknownCardBrandException
     */
    private function getBrand()
    {
        $patterns = [
            self::VISA     => '^4[0-9]{0,}$',
            self::MASTER   => '^(5[1-5]|222[1-9]|22[3-9]|2[3-6]|27[01]|2720)[0-9]{0,}$',
            self::AMEX     => '^3[47][0-9]{0,}$',
            self::DISCOVER => '^(6011|65|64[4-9]|62212[6-9]|6221[3-9]|622[2-8]|6229[01]|62292[0-5])[0-9]{0,}$',
            self::DINERS   => '^3(?:0[0-59]{1}|[689])[0-9]{0,}$',
        ];

        foreach ($patterns as $brand => $pattern) {
            if (preg_match("~{$pattern}~", $this->number)) {
                return $brand;
            }
        }

        throw new UnknownCardBrandException('Unable to determine card brand');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'number' => $this->number,
            'cvv'    => $this->cvv,
            'expiry' => $this->expiry,
            'brand'  => $this->brand,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->number;
    }
}
