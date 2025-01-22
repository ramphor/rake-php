<?php

namespace Ramphor\Rake\Facades;

use Ramphor\Sql as SqlBuilder;

/**
 * @method static string name()
 * @method static string prefix()
 * @method static mixed query(SqlBuilder $query)
 * @method static array get(SqlBuilder $query)
 * @method static mixed var(SqlBuilder $query)
 * @method static object|array row(SqlBuilder $query)
 * @method static bool exists(SqlBuilder $query)
 * @method static int|bool insert(SqlBuilder $query)
 * @method static mixed raw_query(string $sql)
 *
 * @see \Ramphor\Rake\Constracts\Driver
 */
class DB extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'db';
    }
}
