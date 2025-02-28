<?php

namespace App\Lib\Lime;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Facade;

class LimeRequest extends Facade
{
    protected static $client = null;

    public static function getInstance()
    {
        if (! isset(self::$client)){
            self::$client = new Client();
        }

        return self::$client;
    }

    public function __call($method, $args)
    {
        $request  = self::getInstance()->__call($method, $args);
        $response = $request->getBody()->getContents();
        $code     = $request->getStatusCode();

        return [
            $code,
            $response
        ];
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'LimeRequest';
    }
}
