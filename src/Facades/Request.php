<?php

namespace Ramphor\Rake\Facades;

class Request extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'request';
    }
}
