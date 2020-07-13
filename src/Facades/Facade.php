<?php
namespace Ramphor\Rake\Facades;

use Ramphor\Rake\Rake;
use Ramphor\Rake\Exceptions\RuntimeException;

abstract class Facade
{
    protected static $app;

    protected static $resolvedInstance;

    /**
     * Set the application instance
     *
     * @param \Ramphor\Rake\Rake $rake
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    public static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    public static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            return static::$resolvedInstance[$name] = static::$app->resolve($name);
        }
    }

    public static function __callStatic($name, $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return call_user_func_array(
            [$instance, $name],
            $args
        );
    }
}
