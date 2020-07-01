<?php
namespace Ramphor\Rake\Abstracts;

abstract class CrawlerTooth extends Tooth
{
    protected $skipCheckTooth = false;


    public function skipCheckToothWhenCrawl($skip = false)
    {
        $this->skipCheckTooth = (bool)$skip;
    }

    public function crawlUrlOptions()
    {
        return [
            'limit' => 10,
            'crawled' => 0
        ];
    }

    public function fetch()
    {

        $rake = $this->getRake();
        if ($this->skipCheckTooth) {
            $tooth = null;
        } else {
            $tooth = $this;
        }

        $crawlUrls = $this->driver->getCrawlUrls($rake, $tooth, $this->crawlUrlOptions());

        var_dump($crawlUrls);
    }
}
