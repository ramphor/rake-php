<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\HttpClient;

abstract class AbstractHttpClient implements HttpClient
{
    public function __call($name, $args)
    {
    }
}
