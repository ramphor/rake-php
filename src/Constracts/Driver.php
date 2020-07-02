<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Rake;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Tooth;

interface Driver
{
    public function dbQuery(string $sql);

    public function createDbTable(string $tableName, string $syntaxContent);

    public function crawlUrlIsExists(Link $url, Rake $rake, Tooth $tooth = null);

    public function insertCrawlUrl(Link $url, Rake $rake, Tooth $tooth = null);

    public function updateFeedOptions(Feed $feed, $options = null);

    public function getCrawlUrls(Rake $rake, Tooth $tooth = null, $options = []);
}
