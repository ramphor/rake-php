<?php
namespace Ramphor\Rake\Managers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Http\Client\HttpClient;
use Http\Adapter\Guzzle6\Client as HttpAdapter;
use Ramphor\Rake\Facades\Logger;

class RequestManager
{
    protected $httpAdapter;
    protected $clientOptions;

    private function __construct(HttpClient $adapter)
    {
        $this->httpAdapter = $adapter;
    }

    public static function createRequest()
    {
        $httpAdapter = new HttpAdapter(
            new Client()
        );
        return new self($httpAdapter);
    }

    public function sendRequest($method, $url, $options = [])
    {
        if ($options !== $this->clientOptions) {
            $client              = new Client($options);
            $this->httpAdapter   = new HttpAdapter($client);
            $this->clientOptions = $options;
        }

        $request = new Request($method, $url);
        return call_user_func(
            [$this->httpAdapter, 'sendRequest'],
            $request
        );
    }
}
