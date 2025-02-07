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
 * @method static Resource makeResource(string $id, array $data = [])
 * @method static void bind(string $abstract, \Closure|string|null $concrete = null)
 * @method static bool isBound(string $abstract)
 * @method static mixed resolve(string $abstract)
 * @method static Resource findParent(int $childId)
 * @method static int getTotalResources()
 * @method static Resource findByQuery(\Ramohor\Sql $query, callable $callback = null)
 * @method static Resource find(int $resourceId)
 * @method static string generateHash($data, $type)
 * @method static Resource getFromHash($hash, $type)
 * @method static void skipLinkByUrl($url, $tooth)
 *
 * @see \Ramphor\Rake\Abstracts\ResourceManager
 */
class Resources extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'resources';
    }
}
