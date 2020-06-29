<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

class Sitemap extends Base
{
    protected $namespaces = [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1'
    ];
}
