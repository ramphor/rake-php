<?php
namespace Ramphor\Rake;

final class Link
{
    protected $scheme;
    protected $host;
    protected $path;
    protected $query;

    protected $sourceDomain;

    public function __construct($url, $source)
    {
    }

    public function __toString()
    {
    }
}
