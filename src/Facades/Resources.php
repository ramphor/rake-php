<?php

namespace Ramphor\Rake\Facades;

use Ramphor\Rake\Resource;

/**
 * @method static void register(string $id, string $resourceClass)
 * @method static Resource create(string $id, array $data = [])
 * @method static Resource get(string $id)
 * @method static bool has(string $id)
 * @method static array getResources()
 * @method static void clear()
 *
 * @see \Ramphor\Rake\Constracts\ResourceManager
 */
class Resources extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'resources';
    }
}
