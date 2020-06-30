<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Link;

interface Driver
{
    public function dbQuery(string $sql);

    public function createDbTable(string $tableName, string $syntaxContent);

    public function crawlUrlIsExists(Link $url, string $teethId = null);

    public function insertCrawlUrl(Link $url, string $teethId = null);
}
