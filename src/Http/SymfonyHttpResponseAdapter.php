<?php
namespace Rake\Http;

use Rake\Constracts\HttpResponseInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;

class SymfonyHttpResponseAdapter implements HttpResponseInterface
{
    protected SymfonyResponseInterface $response;

    public function __construct(SymfonyResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders(false);
    }

    public function getContent(): string
    {
        return $this->response->getContent();
    }
}