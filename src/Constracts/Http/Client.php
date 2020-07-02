<?php
namespace Ramphor\Rake\Constracts\Http;

interface Client
{
    public function request($method, $uri = '', $options = []);
}
