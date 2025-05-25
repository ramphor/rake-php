<?php

namespace Ramphor\Rake\Abstracts;

use Http\Client\Exception\RequestException;
use Ramphor\Rake\Constracts\Tooth\CrawlerToothConstract;
use Ramphor\Rake\Response;
use Ramphor\Rake\Facades\Db;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Types\CrawledUrl;
use Ramphor\Sql as QueryBuilder;

abstract class CrawlerTooth extends Tooth implements CrawlerToothConstract
{
    protected $validateResponse = false;
    protected $limitQueryUrls = 10;

    protected $isCrawlUrlInContent = true;

    public function crawlRequestOptions()
    {
        return [
            'verify' => false,
        ];
    }

    public function getLimitQueryUrls() {
        return $this->limitQueryUrls;
    }

    abstract protected function validateURL($url);

    protected function validateRequestResponse($response): bool
    {
        return !empty($response);
    }

    public function crawlUrlsQuery(QueryBuilder $query): QueryBuilder
    {
        return $query
            ->orderBy('retry ASC, updated_at ASC, id ASC')
            ->limit($this->getLimitQueryUrls());
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

        $results = DB::get($sql, CrawledUrl::class);

        if (empty($results)) {
            return [];
        }
        return $results;
    }

    public function getResponses()
    {
        $response   = new Response(Response::TYPE_ARRAY);
        $crawlDatas = $this->getCrawlUrls();

        Logger::info(sprintf('Get %d crawl URL(s) in database', count( $crawlDatas)));
        foreach ($crawlDatas as $crawlData) {
            if (!$this->validateURL($crawlData->url)) {
                Logger::info(sprintf(
                    'The URL %s has invalid format',
                    $crawlData->url
                ));
                $response->append($crawlData->url, null, $crawlData->id, 'skip');
                continue;
            }
            try {
                $requestResponse = Request::sendRequest(
                    'GET',
                    $crawlData->url,
                    $this->crawlRequestOptions()
                );
                if (!$this->validateResponse || $this->validateRequestResponse($response)) {
                    $response->append($crawlData->url, $requestResponse->getBody(), $crawlData->id);
                }
                Logger::info(sprintf(
                    'Crawl URL %s is successful with status code %d',
                    $crawlData->url,
                    $requestResponse->getStatusCode()
                ));
            } catch (RequestException $e) {
                ob_start();
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                $errorLogs = ob_get_clean();
                Logger::warning(sprintf('%s\n%s', $e->getMessage(), $errorLogs), (array)$crawlData);
                if (is_callable([$e, 'getResponse'])) {
                    $requestResponse = $e->getResponse();
                    $statusCode      = $requestResponse->getStatusCode();

                    $response->append(
                        $crawlData->url,
                        null,
                        $crawlData->id,
                        $statusCode >= 500 ? 'error' : 'skip'
                    );
                } else {
                    $response->append(
                        $crawlData->url,
                        null,
                        $crawlData->id,
                        (int)$crawlData->retry < 20 ? 'error' : 'skip'
                    );
                }
            }
        }

        // Create a responses list to integrate with Rake structure
        return [$response];
    }

    public function isCrawlUrlInContent()
    {
        return boolval($this->isCrawlUrlInContent);
    }
}
