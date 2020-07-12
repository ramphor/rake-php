<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Response;
use Ramphor\Sql;
use Ramphor\Rake\Facades\Db;
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
            ->limit(2);
    }

    public function getCrawlUrls()
    {
        $sql = sql()->select('*')
                ->from(DB::table('rake_crawled_urls'));

        if ($this->skipCheckTooth) {
            $sql = $sql->where('rake_id=?', $this->rake->getId());
        } else {
            $sql = $sql->where(
                'rake_id=? AND tooth_id=?',
                $this->rake->getId(),
                $this->getId()
            );
        }
        $sql = $this->crawlUrlsQuery($sql);

        return DB::get($sql);
    }

    public function fetch(): Response
    {
        $response   = new Response(Response::TYPE_ARRAY);
        $crawlDatas = $this->getCrawlUrls();

        var_dump($crawlDatas);
        die;

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
