<?php
namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Abstracts\Feed;

class CsvFileFeed extends Feed
{
    const FEED_NAME = 'csv_file';

    protected $hasResponse = true;
    protected $csvFilePath = '';

    public function get_name()
    {
        return self::FEED_NAME;
    }

    public function parseArgs($args)
    {
        if (isset($args['path'])) {
            $this->csvFilePath = $args['path'];
        }
    }

    public function execute()
    {
        if (!$this->csvFilePath) {
            return;
        }
        return fopen($this->csvFilePath, 'r');
    }

    public function valid()
    {
        return $this->csvFilePath !== '';
    }

    public function next()
    {
        // Csv file does not have next page
        return false;
    }

    public function rewind()
    {
    }
}
