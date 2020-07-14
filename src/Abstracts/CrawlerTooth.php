<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Response;
use Ramphor\Sql;
use Ramphor\Rake\Facades\Db;
use Ramphor\Rake\Facades\Client;
use Ramphor\Sql as QueryBuilder;

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

    public function crawlUrlsQuery(QueryBuilder $query): QueryBuilder
    {
        return $query
            ->orderBy('retry ASC, updated_at ASC, ID ASC')
            ->limit(10);
    }

    public function getCrawlUrls()
    {
        $sql = sql()->select('*')
                ->from(DB::table('rake_crawled_urls'));

        if ($this->skipCheckTooth) {
            $sql = $sql->where(
                'rake_id=? AND tooth_id IS NULL AND crawled=? AND skipped=?',
                $this->rake->getId(),
                0,
                0
            );
        } else {
            $sql = $sql->where(
                'rake_id=? AND tooth_id=? AND crawled=? AND skipped=?',
                $this->rake->getId(),
                $this->getId(),
                0,
                0
            );
        }
        $sql = $this->crawlUrlsQuery($sql);

        return DB::get($sql);
    }

    public function fetch(): Response
    {
        $response   = new Response(Response::TYPE_ARRAY);
        $crawlDatas = $this->getCrawlUrls();

        foreach ($crawlDatas as $crawlData) {
            if (!$this->validateURL($crawlData->url)) {
                $response->append($crawlData->url, null, $crawlData->ID, true);
                continue;
            }
            $html = Client::request(
                'GET',
                $crawlData->url,
                $this->crawlRequestOptions()
            );
            if (!$this->validateResponse || $this->validateRequestResponse($response)) {
                $response->append($crawlData->url, $html->getBody(), $crawlData->ID);
            }
        }

        return $response;
    }
}
