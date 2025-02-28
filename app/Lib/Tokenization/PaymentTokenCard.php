<?php

namespace App\Lib\Tokenization;

use App\Lib\CreditCard;
use App\Lib\KVS\PaymentTokenKeyValuePair as PaymentStore;
use Carbon\Carbon;

class PaymentTokenCard
{
    private $card;
    private $token;
    private $expires_at;

    public function __construct(CreditCard $card = null)
    {
        if ($card) {
            $this->card = $card;
        }
    }

    /**
     * @param CreditCard $card
     * @return PaymentTokenCard
     */
    public static function make(CreditCard $card)
    {
        return new static($card);
    }

    /**
     * @param string $token
     * @return PaymentTokenCard
     */
    public static function loadToken(string $token)
    {
        return (new static)->set('token', $token);
    }

    /**
     * @return string $key
     */
    public function store()
    {
        $store = PaymentStore::make()
           ->setEx($this->generatePayload());

        $this->token      = $store->key();
        $this->expires_at = $store->getExpiry();

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function get()
    {
        if (! $this->token) {
            throw new \Exception('Missing token');
        } elseif (! ($encrypted = PaymentStore::make($this->token)->get())) {
            throw new \Exception('Invalid token');
        }

        $this->card = $this->parsePayload($encrypted);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function consume()
    {
        if (! $this->token) {
            throw new \Exception('Missing token');
        }

        PaymentStore::make($this->token)->del();
    }

    /**
     * @param $prop
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function set($prop, $value)
    {
        if (! property_exists($this, $prop)) {
            throw new \Exception("Unknown property '{$prop}'");
        }

        $this->$prop = $value;

        return $this;
    }

    /**
     * @return string
     */
    private function generatePayload()
    {
        return \payment_source::encrypt_credit_card(\json_encode($this->card->toArray()), null, false);
    }

    /**
     * @param string $encrypted
     * @return CreditCard
     * @throws \Exception
     */
    private function parsePayload(string $encrypted)
    {
        return CreditCard::make()->set(\json_decode(\payment_source::decrypt_credit_card($encrypted), true));
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return Carbon|null
     */
    public function getExpiry()
    {
        return $this->expires_at;
    }

    /**
     * @return CreditCard
     */
    public function card()
    {
        return $this->card;
    }
}
