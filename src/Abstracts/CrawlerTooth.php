<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Http\Response;

abstract class CrawlerTooth extends Tooth
{
    protected $skipCheckTooth = false;
    protected $validateResponse = false;

    public function skipCheckToothWhenCrawl($skip = false)
    {
        $this->skipCheckTooth = (bool)$skip;
    }

    public function crawlOptions()
    {
        return [
            'limit' => 2,
            'crawled' => 0
        ];
    }

    public function crawlRequestOptions()
    {
        return [];
    }

    abstract protected function validateURL($url);

    protected function validateRequestResponse($response): bool
    {
        return !empty($response);
    }

    public function fetch(): Response
    {
        $response = new Response(Response::TYPE_ARRAY);
        $rake     = $this->getRake();

        if ($this->skipCheckTooth) {
            $tooth = null;
        } else {
            $tooth = $this;
        }

        $crawlDatas = $this->driver->getCrawlURLs($rake, $tooth, $this->crawlOptions());

        foreach ($crawlDatas as $crawlData) {
            if (!$this->validateURL($crawlData->url)) {
                continue;
            }
            $html = $this->httpClient->request(
                'GET',
                $crawlData->url,
                $this->crawlRequestOptions()
            );
            if (!$this->validateResponse || $this->validateRequestResponse($response)) {
                $response->append($crawlData->url, $html, $crawlData->ID);
            }
        }

        return $response;
    }
}
