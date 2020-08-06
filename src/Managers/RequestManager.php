<?php
namespace Ramphor\Rake\Managers;

use Psr\Http\Message\RequestInterface;
use Http\Client\HttpClient;
use GuzzleHttp\Psr7\Request;

class RequestManager
{
    protected $httpAdapter;

    private function __construct()
    {
    }

    public static function createRequest(HttpClient $httpAdapter)
    {
        $request = new static();
        $request->setAdapter($httpAdapter);

        return $request;
    }

    protected function setAdapter(HttpClient $httpAdapter)
    {
        $this->httpAdapter = $httpAdapter;
    }

    public function sendRequest($method, $url, $options = [])
    {
        $request = new Request($method, $url, $options);
        return call_user_func(
            [$this->httpAdapter, 'sendRequest'],
            $request
        );
    }
}
