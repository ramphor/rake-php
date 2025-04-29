<?php

namespace Ramphor\Rake\Facades;

/**
 * Class Logger
 *
 * Alias của Monolog Logger với các phương thức logging sau:
 *
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log(string $level, string $message, array $context = [])
 *
 * @package Ramphor\Logger
 */

class Logger extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'logger';
    }
}
