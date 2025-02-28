<?php

namespace App\Lib\KVS;

use Illuminate\Support\Facades\Redis;

class RedisKeyValueStore implements iKeyValueStore
{
    private $prefix;

    public function __construct(string $prefix = '')
    {
        $this->prefix = sprintf('%s%s:', $prefix ? "{$prefix}:" : '', CRM_APP_KEY);
    }

    public function key(string $key)
    {
        return $this->prefix . $key;
    }

    public function get(string $key)
    {
        return Redis::get($this->key($key));
    }

    public function set(string $key, string $value)
    {
        Redis::set($this->key($key), $value);

        return $this;
    }

    public function setEx(string $key, string $value, int $expires)
    {
        Redis::setEx($this->key($key), $expires, $value);

        return $this;
    }


    public function del(string $key)
    {
        return Redis::del($this->key($key));
    }

    public function exists(string $key)
    {
        return Redis::exists($key);
    }

    public function ttl(string $key)
    {
        return Redis::ttl($key);
    }

    public function expire(string $key, int $expires)
    {
        return Redis::expire($key, $expires);
    }

    public function getKeys(string $pattern)
    {
        return collect(Redis::keys($pattern));
    }

    public function count(string $pattern)
    {
        return $this->getKeys($pattern)->count();
    }
}
