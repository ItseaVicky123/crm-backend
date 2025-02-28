<?php

namespace App\Lib\KVS;

class KeyValueStore implements iKeyValueStore
{
    private $driver;

    public function __construct(string $prefix = '')
    {
        $driver = 'App\\Lib\\KVS\\' . ucfirst(config('kvs.driver') . 'KeyValueStore');

        if (! class_exists($driver)) {
            throw new \Exception("Invalid driver '{$driver}'");
        }

        $this->driver = new $driver($prefix);
    }

    public static function make(string $prefix = '')
    {
        return new static($prefix);
    }

    public function set(string $key, string $value)
    {
        $this->driver->set($key, $value);

        return $this;
    }

    public function setEx(string $key, string $value, int $expires)
    {
        $this->driver->setEx($key, $value, $expires);

        return $this;
    }

    public function get(string $key)
    {
        return $this->driver->get($key);
    }

    public function del(string $key)
    {
        return $this->driver->del($key);
    }

    public function exists(string $key)
    {
        return $this->driver->exists($key);
    }

    public function ttl(string $key)
    {
        return $this->driver->ttl($key);
    }

    public function expire(string $key, int $expires)
    {
        return $this->driver->expire($key, $expires);
    }

    public function getKeys(string $pattern)
    {
        return $this->driver->getKeys($pattern);
    }

    public function count(string $pattern)
    {
        return $this->driver->count($pattern);
    }
}
