<?php

namespace Ramphor\Rake\Managers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Http\Adapter\Guzzle6\Client as HttpAdapter;
use Psr\Http\Message\ResponseInterface;

class RequestManager
{
    protected $httpAdapter;
    protected $clientOptions;

    private function __construct(HttpClient $adapter)
    {
        $this->httpAdapter = $adapter;
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    public static function createRequest()
    {
        $httpAdapter = new HttpAdapter(
            new Client()
        );
        return new self($httpAdapter);
    }

    public function sendRequest($method, $url, $options = []): ResponseInterface
    {
        if ($options !== $this->clientOptions) {
            $client              = new Client($options);
            $this->httpAdapter   = new HttpAdapter($client);
            $this->clientOptions = $options;
        }


        $request  = new Request($method, $url);
        $response = $this->httpAdapter->sendRequest($request);

        return $response;
    }
}
