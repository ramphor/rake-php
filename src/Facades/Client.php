<?php
namespace Ramphor\Rake\Facades;

class Client extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'http';
    }
}
