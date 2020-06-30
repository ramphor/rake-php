<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Feed;
use Ramphor\Rake\Abstracts\AbstractDriver;

abstract class AbstractFeed implements Feed
{
    const LIFE_CYCLE_ONE_TIME = 1;

    protected $lifeCycle = self::LIFE_CYCLE_ONE_TIME;
    protected $allowedLifeCycles = [
        self::LIFE_CYCLE_ONE_TIME,
    ];
    protected $driver;
    protected $httpClient;

    public function setLifeCycle($lifeCycle)
    {
        if (in_array($lifeCycle, $this->allowedLifeCycles)) {
            throw new \Exception(sprintf("Invalid life cycle %d", $lifeCycle));
        }
        $this->lifeCycle = $lifeCycle;
    }

    public function setHttpClient(AbstractHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setDriver(AbstractDriver $driver)
    {
        $this->driver = $driver;
    }

    public function insertCrawlUrl(Link $url)
    {
        if (empty($this->driver)) {
            throw new \Exception("Rake driver is not exists");
        }

        if (!$this->driver->crawlUrlIsExists($url, $this->rakeId)) {
            $this->driver->insertCrawlUrl($url, $this->rakeId);
        }
    }
}
