<?php
namespace Ramphor\Rake\Abstracts;

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

    public function setHttpClient(HttpClient &$httpClient)
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

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}
