<?php
namespace Ramphor\Scrapy;

use Ramphor\Scrapy\Constracts\Adapter as AdapterConstract;

abstract class Adapter implements AdapterConstract
{
    public function generateHashFromURL($url) {
    }
}
