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
            'limit' => 2,
            'crawled' => 0
        ];
    }

    public function crawlRequestOptions() {
        return [];
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
        $responses = [];

        foreach($crawlUrls as $crawlUrl) {
            $response = $this->httpClient->request(
                'GET',
                $crawlUrl->url,
                $this->crawlRequestOptions()
            );
            $responses[$crawlUrl->url] = [
                'raw' => $crawlUrl,
                'response' => $response,
            ];
        }

        return $responses;
    }
}
