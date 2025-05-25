<?php

namespace Ramphor\Rake\Constracts\Tooth;

use Ramphor\Rake\Constracts\Tooth;
use Ramphor\Sql as QueryBuilder;

interface CrawlerToothConstract extends Tooth
{
    public function crawlRequestOptions();

    public function crawlUrlsQuery(QueryBuilder $query): QueryBuilder;

    public function getLimitQueryUrls();

    public function getCrawlUrls();

    public function getResponses();


    public function isCrawlUrlInContent();
}
