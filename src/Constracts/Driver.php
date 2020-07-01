<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Link;

interface Driver
{
    public function dbQuery(string $sql);

    public function createDbTable(string $tableName, string $syntaxContent);

    public function crawlUrlIsExists(Link $url, string $rakeId = null);

    public function insertCrawlUrl(Link $url, string $rakeId = null);

    public function updateFeedOptions(Feed $feed, $options = null);
}
