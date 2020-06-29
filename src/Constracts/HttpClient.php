<?php
namespace Ramphor\Rake\Constracts;

interface HttpClient
{
    public function request($method, $uri = '', $options = []);
}
