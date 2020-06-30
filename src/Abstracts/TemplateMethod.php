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

    public function setHttpClient(AbstractHttpClient &$httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setDriver(AbstractDriver &$driver)
    {
        $this->driver = $driver;
    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    public function getHttpClient(): AbstractHttpClient
    {
        return $this->httpClient;
    }
}
