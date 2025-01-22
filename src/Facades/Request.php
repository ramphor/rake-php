<?php

namespace Ramphor\Rake\Facades;

/**
 * @method static \Psr\Http\Message\ResponseInterface sendRequest(string $method, string $url, array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface get(string $url, array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface post(string $url, array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface put(string $url, array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface delete(string $url, array $options = [])
 * @method static \Psr\Http\Message\ResponseInterface head(string $url, array $options = [])
 *
 * @see \Ramphor\Rake\Managers\RequestManager
 */
class Request extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'request';
    }
}
