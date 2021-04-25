<?php
namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Abstracts\Feed;

class CsvFile extends Feed
{
    const FEED_NAME = 'csv_file';

    public function get_name()
    {
        return self::FEED_NAME;
    }

    public function execute()
    {
    }

    public function valid()
    {
    }

    public function next()
    {
    }

    public function rewind()
    {
    }
}
