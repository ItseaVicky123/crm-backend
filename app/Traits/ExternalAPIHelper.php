<?php

namespace App\Traits;

use App\Lib\Lime\LimeRequest;
use App\Models\ProviderAttribute;
use App\Providers\External\BigCommerceServiceProvider;
use configSettings;
use fileLogger;
use Illuminate\Support\Facades\Log;
use data_security;
use Psr\Http\Message\UriInterface;
use Illuminate\Support\Str;
use GuzzleHttp\TransferStats;

/**
 * Trait ExternalAPIHelper
 * @package App\Traits
 * @method mixed get(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 * @method mixed head(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 * @method mixed put(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 * @method mixed post(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 * @method mixed patch(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 * @method mixed delete(string|UriInterface $uri, array $data = [], $headers = [], bool $log = true)
 */
trait ExternalAPIHelper
{
    use Setter;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $uriDomain;

    /**
     * @var string
     */
    protected $sendType = 'json';

    /**
     * @var string
     */
    protected $uriProtocol = 'https';

    /**
     * @var array
     */
    protected $args;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var int
     */
    protected int $httpStatusCode = 200;

    /*
     * @var int
     */
    protected $genericId = 0;

    /**
     * @var array
     */
    protected $maps = [];

    /**
     * @var int
     */
    protected $timeout = 90;

    /**
     * @var string
     */
    protected string $uuid;

    /**
     * Request parameter keys that should be obfuscated when logging
     * the data we send to the external API
     * @var array
     */
    protected $obfuscationKeys = [];

    /**
     * @var string
     */
    protected string $proxyUrl    = "zproxy.lum-superproxy.io:22225";

    /**
     * @var bool
     */
    protected bool $enableProxy  = false;

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param $args
     * @return $this
     */
    public function setArgs($args)
    {
        foreach ($args as $arg => $val) {
            $this->setArg($arg, $val);
        }

        return $this;
    }

    public function setArg($arg, $val)
    {
        $this->args[$arg] = $val;

        return $this;
    }

    /**
     * @param $arg
     * @return $this
     */
    public function delArg($arg)
    {
        unset($this->args[$arg]);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param $protocol
     * @return $this
     */
    public function setUriProtocol($protocol)
    {
        $this->uriProtocol = $protocol;

        return $this;
    }

    /**
     * @param $domain
     * @return $this
     */
    public function setUriDomain($domain)
    {
        $this->uriDomain = $domain;

        return $this;
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function enableProxy(bool $flag = false) : void
    {
        $attribute = ProviderAttribute::where('provider_type_id', 'ENABLE_IP_RANDOMIZER')->first();
        if (!$attribute || !$attribute->is_active)
        {
            // if given attribute not found or 0, global proxy ip setting disabled
            $this->enableProxy = false;
        }
        else
        {
            $this->enableProxy = $flag;
        }
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds)
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * @return bool
     */
    public function wasSuccess() :bool
    {
        return in_array($this->httpStatusCode, [200, 201, 204]);
    }

    /**
     * @param $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setHeader($key, $value = null)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @return $this
     * @TODO correct spelling of below function name
     */
    public function addOfuscationKey($key)
    {
        if (!in_array($key, $this->obfuscationKeys)) {
            $this->obfuscationKeys[] = $key;
        }

        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     */
    public function setObfuscationKeys(array $keys): self
    {
        $this->obfuscationKeys = array_unique($keys);

        return $this;
    }

    /**
     * @param $sendType
     * @return $this
     */
    public function setSendType($sendType)
    {
        $this->sendType = $sendType;

        return $this;
    }

    /**
     * @param $username
     * @param $password
     */
    public function basicAuth($username, $password)
    {
        $this->setArg('auth', [$username, $password]);
    }

    /**
     * @param array $data
     * @param string $direction
     */
    protected function log(array $data, string $direction = 'request'): void
    {
        $uuid = $this->getUuid();
        $name = class_basename($this);
        $data = array_merge($data, ['uuid' => $uuid]);

        Log::debug("$name External $direction", $data);

        if ($this instanceof BigCommerceServiceProvider) {
            $this->logToBcBridge($data, $uuid, $direction);
        }
    }

    /**
     * @return string
     */
    protected function generateUuid(): string
    {
        return $this->uuid = (string) Str::uuid();
    }

    /**
     * @return string
     */
    protected function getUuid(): string
    {
        return $this->uuid ?? $this->generateUuid();
    }

    /**
     * @param bool $log
     * @return mixed
     */
    protected function send(bool $log = true)
    {
        $uri  = $this->getUri();
        $args = $this->getArgs();
        $this->generateUuid();

        if ($log) {
            if (isset($args['body']) && substr(ltrim($args['body']), 0, 5) == "<?xml") {
                $safeParams = data_security::ObfuscateXML($args['body'], $this->obfuscationKeys);
            } else {
                $safeParams = data_security::ObfuscateArray($args, $this->obfuscationKeys);
            }

            $logData = [
                'uri'        => $uri,
                'parameters' => $safeParams,
            ];

            $this->log($logData);
        }
        $response             = LimeRequest::{$this->type}($uri, $args);
        $this->httpStatusCode = $response[0];

        if ($log) {
            $logData = ['response' => $response[1]];
            $this->log($logData, 'response');
        }

        return $this->parseResponse($response[1]);
    }

    /**
     * @param $response
     * @return mixed
     */
    protected function parseResponse($response)
    {
        return $response;
    }

    /**
     * @return array
     */
    protected function formatHeaders()
    {
        $formatted = [];

        foreach ($this->headers as $key => $val) {
            $formatted[] = "{$key}: {$val}";
        }

        return $formatted;
    }

    /**
     * @return string
     */
    protected function getUri()
    {
        return sprintf(
            '%s://%s/%s',
            $this->uriProtocol,
            $this->uriDomain,
            ltrim($this->uri, '/')
        );
    }

    /**
     * @param $method
     * @param $params
     * @return mixed|null
     */
    public function __call($method, $params)
    {
        $map = [
            'get',
            'post',
            'put',
            'patch',
            'delete',
        ];

        if (in_array($method, $map)) {
            $this->type = $method;
            $headers    = $this->getHeaders();
            $log        = true;

            if (isset($params[0])) {
                $this->setUri($params[0]);
            }

            if (isset($params[1])) {
                $this->setData($params[1]);
            } else if (in_array($this->type, ['post', 'put', 'patch'])) {
                $this->setData('');
            } else {
                $this->setData(null);
            }

            if (isset($params[2])) {
                $headers = array_merge($headers, $params[2]);
            }

            if (isset($params[3])) {
                $log = (bool) $params[3];
            }

            $this->setArgs([
                $this->sendType => $this->getData(),
                'headers'       => $headers,
                'http_errors'   => false,
                'timeout'       => $this->timeout,
            ]);

            if ($this->enableProxy)
            {
                $proxyIdPwd = (new configSettings())->fetchValueFromKey('IP_RANDOMIZER_PROXY_ID_PASS');
                // proxy url contains userid pass and url
                $this->setArgs(
                    [
                        'proxy' => "https://" . $proxyIdPwd . '@' . $this->proxyUrl,
                        'verify' => false,
                        'on_stats' => function (TransferStats $stats) {
                            $resp_stats = $stats->getHandlerStats();
                            $primary_ip = $resp_stats['primary_ip'] ?? '';
                            fileLogger::log_flow('Used Guzzle Proxy to change the IP Address for given payment gateway', ':IP_RANDOMIZER_PROXY_IP: => ' . $primary_ip);
                        }
                    ],
                );
            }

            return $this->send($log);
        } elseif (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return null;
    }

    /**
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}
