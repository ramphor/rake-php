<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Feed as FeedConstract;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;

abstract class Feed extends TemplateMethod implements FeedConstract
{
    const LIFE_CYCLE_ONE_TIME = 1;

    protected $lifeCycle = self::LIFE_CYCLE_ONE_TIME;
    protected $allowedLifeCycles = [
        self::LIFE_CYCLE_ONE_TIME,
    ];

    protected $id;
    protected $tooth;

    public function __construct(Tooth $tooth, string $feedId)
    {
        $this->setId($feedId);
        $this->setDriver($tooth->getDriver());
        $this->setHttpClient($tooth->getHttpClient());
        $this->setTooth($tooth);
    }

    public function setTooth(Tooth $tooth)
    {
        $this->tooth = $tooth;
    }

    public function getTooth(): Tooth
    {
        return $this->tooth;
    }

    public function setLifeCycle($lifeCycle)
    {
        if (in_array($lifeCycle, $this->allowedLifeCycles)) {
            throw new \Exception(sprintf("Invalid life cycle %d", $lifeCycle));
        }
        $this->lifeCycle = $lifeCycle;
    }

    public function insertCrawlUrl(Link $url)
    {
        if (empty($this->driver)) {
            throw new \Exception("Rake driver is not exists");
        }

        $rakeId = $this->getTooth()->getRake()->getId();

        if (!$this->driver->crawlUrlIsExists($url, $rakeId)) {
            $this->driver->insertCrawlUrl($url, $rakeId);
        }
    }

    public function updateOptions($options = null)
    {
        $this->driver->updateFeedOptions($this, $options);
    }
}
