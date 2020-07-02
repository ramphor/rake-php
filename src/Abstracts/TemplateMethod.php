<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Abstracts\Http\Client;

abstract class TemplateMethod
{
    protected $id;
    protected $driver;
    protected $httpClient;

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setHttpClient(Client &$httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setDriver(Driver &$driver)
    {
        $this->driver = $driver;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }
}
