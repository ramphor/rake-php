<?php

namespace Ramphor\Rake\Feeds\Sitemap;

use Ramphor\Rake\Abstracts\Feed;

class SitemapIndexFeed extends Feed
{
    const NAME = 'sitemap_index';

    public function get_name()
    {
        return static::NAME;
    }

    public function fetch()
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
