<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Link;
use Ramphor\Rake\Facades\Option;
use Ramphor\Rake\Constracts\Feed as FeedConstract;

abstract class Feed implements FeedConstract
{
    protected $hasResponse           = false;
    protected $trimLastSplashURL     = true;

    protected $id;
    protected $tooth;
    protected $options;

    public function __construct(string $feedId = null, Tooth $tooth = null)
    {
        if (!is_null($feedId)) {
            $this->setId($feedId);
        }
        if (!is_null($tooth)) {
            $this->setTooth($tooth);
        }
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

    public function urlExists(Link $url)
    {
        $tooth = $this->getTooth();
        $rake  = $tooth->getRake();

        $sql = sql()->select("id")
            ->from(DB::table('rake_crawled_urls'))
            ->where(
                'url=? AND rake_id=? AND tooth_id=?',
                (string)$url,
                $rake->getId(),
                $tooth->getId()
            );

        return DB::exists($sql);
    }

    public function insertCrawlUrl(Link $url)
    {
        $tooth = $this->getTooth();
        $rake = $tooth->getRake();

        if ($tooth->isSkipCheckTooth()) {
            $toothId = null;
        } else {
            $toothId = $tooth->getId();
        }

        if (!$this->urlExists($url)) {
            $sql = sql()->insertInto(
                DB::table('rake_crawled_urls'),
                ['url', 'rake_id', 'tooth_id', 'crawled', 'retry', 'created_at', 'updated_at']
            )
            ->values('?, ?, ?, ?, ?, @, @', (string)$url, $rake->getId(), $toothId, 0, 0, 'NOW()', 'NOW()');

            return DB::query($sql);
        }
    }

    public function hasResponse()
    {
        return (bool)$this->hasResponse;
    }

    protected function getAllOptions()
    {
        return $this->options = Option::get(
            sprintf('rake_feed_%s_options', $this->id),
            array()
        );
    }

    public function getOption($name, $defaultValue = null)
    {
        if (is_null($this->options)) {
            $this->getAllOptions();
        }

        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $defaultValue;
    }

    public function updateOption($name, $value)
    {
        if (is_null($this->options)) {
            $this->getAllOptions();
        }

        $this->options[$name] = $value;

        return Option::update(
            sprintf('rake_feed_%s_options', $this->id),
            $this->options
        );
    }

    public function execute()
    {
        return $this->fetch();
    }
}
