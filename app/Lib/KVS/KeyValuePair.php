<?php

namespace App\Lib\KVS;

use Carbon\Carbon;

class KeyValuePair
{
    const SCOPE   = 'DEFAULT';
    const EXPIRES = 3600;

    private $store;

    protected $key;
    protected $expires_at;


    public function __construct(string $key = null)
    {
        if ($key) {
            $this->key = $key;
        } else {
            $this->key = $this->generate_key();
        }

        $this->store = new KeyValueStore($this::SCOPE);
    }

    /**
     * @param string|null $key
     * @return KeyValuePair
     */
    public static function make(string $key = null)
    {
        return new static($key);
    }

    /**
     * @return string $key
     */
    public function key()
    {
        return $this->key;
    }

    public function getExpiry()
    {
        return $this->expires_at;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->store->get($this->key);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function set(string $value)
    {
        $this->store->set($this->key, $value);

        return $this;
    }

    /**
     * @param string $value
     * @param int|null $expires
     * @return $this
     */
    public function setEx(string $value, int $expires = null)
    {
        $expires = $expires ?? $this::EXPIRES;

        $this->expires_at = Carbon::now()->addSeconds($expires);

        $this->store->setEx($this->key, $value, $expires);

        return $this;
    }

    /**
     * @return mixed
     */
    public function del()
    {
        return $this->store->del($this->key);
    }

    /**
     * @return boolean
     */
    public function exists()
    {
        return $this->store->exists($this->key);
    }

    /**
     * @return int
     */
    public function ttl()
    {
        return $this->store->ttl($this->key);
    }

    /**
     * @param int $expires
     * @return mixed
     */
    public function expire(int $expires)
    {
        return $this->store->expire($this->key, $expires);
    }

    /**
     * @return string
     */
    protected function generate_prefix()
    {
        return self::SCOPE;
    }

    /**
     * @return string
     */
    protected function generate_key()
    {
        return (string) new \uuid();
    }

    /**
     * @param \App\Lib\KVS\string $pattern
     * @return mixed
     */
    public function getKeysFromStore(string $pattern)
    {
        return $this->store->getKeys($pattern);
    }
}
