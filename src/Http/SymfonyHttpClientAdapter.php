<?php
namespace Rake\Http;

use Rake\Constracts\HttpClientInterface;
use Rake\Constracts\HttpResponseInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;

class SymfonyHttpClientAdapter implements HttpClientInterface
{
    protected SymfonyHttpClientInterface $client;

    public function __construct(array $defaultOptions = [])
    {
        $this->client = HttpClient::create($defaultOptions);
    }

    public function request(string $method, string $url, array $options = []): HttpResponseInterface
    {
        $response = $this->client->request($method, $url, $options);
        return new SymfonyHttpResponseAdapter($response);
    }
}