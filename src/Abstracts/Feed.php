<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Feed as FeedConstract;

abstract class Feed implements FeedConstract
{
    const LIFE_CYCLE_ONE_TIME = 1;

    protected $lifeCycle = self::LIFE_CYCLE_ONE_TIME;
    protected $allowedLifeCycles = [
        self::LIFE_CYCLE_ONE_TIME,
    ];

    protected $id;
    protected $tooth;
    protected $options;

    public function __construct(Tooth $tooth, string $feedId)
    {
        $this->setId($feedId);
        $this->setTooth($tooth);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
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

    public function getLifeCycle()
    {
        return $this->lifeCycle;
    }

    public function insertCrawlUrl(Link $url, $checkingTooth = true)
    {
        $tooth = $this->getTooth();
        $rake = $tooth->getRake();

        if (!$checkingTooth) {
            $tooth = null;
        }

        if (!$this->driver->crawlUrlIsExists($url, $rake, $tooth)) {
            $this->driver->insertCrawlUrl($url, $rake, $tooth);
        }
    }

    public function updateOption($option, $value)
    {
        if (is_null($this->options)) {
            $this->options = $this->getOptions();
        }
        $this->options[$option] = $value;

        $this->driver->updateFeedOptions($this, $this->options);
    }

    public function getOption($optionName, $defaultValue = false)
    {
        if (is_null($this->options)) {
            $this->options = $this->getOptions();
        }

        if (!isset($this->options[$optionName])) {
            return $defaultValue;
        }
        return $this->options[$optionName];
    }

    public function getOptions()
    {
        $tooth = $this->getTooth();
        $rake  = $tooth->getRake();

        $sql = sql()->select('options')
            ->from(DB::prefix() . 'rake_feeds')
            ->where(
                'rake_id = ? AND feed_id = ? AND tooth_id = ?',
                $rake->getId(),
                $this->getId(),
                $tooth->getId()
            );

        $options = DB::var($sql);

        if (empty($options)) {
            return [];
        }
        return unserialize($options);
    }
}
