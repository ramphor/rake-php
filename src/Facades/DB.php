<?php
namespace Ramphor\Rake\Facades;

use Ramphor\Sql;

class DB extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'db';
    }
}
