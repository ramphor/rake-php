<?php

namespace Ramphor\Rake\Facades;

use Ramphor\Rake\Instance;

/**
 * @method static void register(string $id, string $instanceClass)
 * @method static Instance create(string $id, array $data = [])
 * @method static Instance get(string $id)
 * @method static bool has(string $id)
 * @method static array getInstances()
 * @method static void clear()
 * @method static void execute(string $id)
 * @method static void executeAll()
 * @method static void stop(string $id)
 * @method static void stopAll()
 * @method static bool isRunning(string $id)
 * @method static void setConfig(string $id, array $config)
 * @method static array getConfig(string $id)
 *
 * @see \Ramphor\Rake\Managers\InstanceManager
 */
class Instances extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'instances';
    }
}
