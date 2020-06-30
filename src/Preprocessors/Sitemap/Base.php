<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use Ramphor\Rake\Abstracts\AbstractPreprocessor;

abstract class Base extends AbstractPreprocessor
{
    protected $rakeId;
    protected $url;

    public function __construct($rakeId, $sitemapURL)
    {
        $this->rakeId = $rakeId;
        $this->url = $sitemapURL;
    }

    public function getId()
    {
        return $this->url;
    }
}
