<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Driver as DriverConstract;

abstract class Driver implements DriverConstract
{
    public function parseCrawlURLOptions($options = [])
    {
        $limit = 10;
        if (isset($options['limit'])) {
            $limit = $options['limit'];
        }
        $crawled = 0;
        if (isset($options['crawled'])) {
            $crawled = $options['crawled'];
        }
        $retry = 'ASC';
        if (isset($options['retry']) && in_array(strtoupper($options['retry']), ['ASC', 'DESC'])) {
            $retry = strtoupper($options['retry']);
        }

        return [$crawled, $retry, $limit];
    }

    public function generateHashFromURL($url)
    {
    }
}
