<?php

namespace App\Lib\KVS;

interface iKeyValueStore
{
   function set(string $key, string $value);
   function setEx(string $key, string $value, int $expires);
   function get(string $key);
   function del(string $key);
   function exists(string $key);
   function ttl(string $key);
   function expire(string $key, int $expires);
   function getKeys(string $pattern);
   function count(string $pattern);
}