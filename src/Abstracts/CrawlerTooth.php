<?php
namespace Ramphor\Rake\Abstracts;

use Psr\Http\Client\ClientExceptionInterface;
use Ramphor\Rake\Response;
use Ramphor\Sql;
use Ramphor\Rake\Facades\Db;
use Ramphor\Rake\Facades\Client;
use Ramphor\Sql as QueryBuilder;

abstract class CrawlerTooth extends Tooth
{
    protected $validateResponse = false;

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

    public function getResponses()
    {
        $response   = new Response(Response::TYPE_ARRAY);
        $crawlDatas = $this->getCrawlUrls();

        foreach ($crawlDatas as $crawlData) {
            if (!$this->validateURL($crawlData->url)) {
                $response->append($crawlData->url, null, $crawlData->ID, 'skip');
                continue;
            }
            try {
                $html = Client::request(
                    'GET',
                    $crawlData->url,
                    $this->crawlRequestOptions()
                );
                if (!$this->validateResponse || $this->validateRequestResponse($response)) {
                    $response->append($crawlData->url, $html->getBody(), $crawlData->ID);
                }
            } catch (ClientExceptionInterface $e) {
                if ($e->hasResponse()) {
                    $requestResponse = $e->getResponse();
                    $statusCode      = $requestResponse->getStatusCode();

                    $response->append(
                        $crawlData->url,
                        null,
                        $crawlData->ID,
                        $statusCode >= 500 ? 'error' : 'skip'
                    );
                } else {
                    $response->append(
                        $crawlData->url,
                        null,
                        $crawlData->ID,
                        (int)$crawlData->retry < 20 ? 'error' : 'skip'
                    );
                }
            }
        }

        return [$response];
    }
}
