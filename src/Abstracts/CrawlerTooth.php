<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Http\Response;
use Ramphor\Sql;
use Ramphor\Rake\Facades\Db;

abstract class CrawlerTooth extends Tooth
{
    protected $skipCheckTooth   = false;
    protected $validateResponse = false;

    public function skipCheckToothWhenCrawl($skip = false)
    {
        $this->skipCheckTooth = (bool)$skip;
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

    public function getCrawlUrls($skipTooth)
    {
        $sql = Sql::select()
            ->from(Db::table('rake_crawled_urls'))
            ->where('rake_id = ?', $this->rake->getId());
    }

    public function fetch(): Response
    {
        $response   = new Response(Response::TYPE_ARRAY);
        $crawlDatas = $this->getCrawlUrls();

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
