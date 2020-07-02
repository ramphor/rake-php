<?php
namespace Ramphor\Rake\Http\Client;

use Requests;
use Ramphor\Rake\Abstracts\Http\Client;

class WordPress extends HttpClient
{
    public function request($method, $uri = '', $options = [])
    {
        $headers = [];
        if (isset($options['headers'])) {
            $headers = $options['headers'];
            unset($options['headers']);
        }

        $data = [];
        if (isset($options['form_params'])) {
            $data = $options['form_params'];
            unset($options['form_params']);
        }

        $response = Requests::request($uri, $headers, $data, strtoupper($method), $options);
        return $response->body;
    }
}
