<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use Ramphor\Rake\Abstracts\Feed;

class SitemapIndex extends Feed
{
    const NAME = 'sitemap_index';

    public function get_name()
    {
        return static::NAME;
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
