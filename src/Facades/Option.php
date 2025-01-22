<?php

namespace Ramphor\Rake\Facades;

/**
 * @method static void set(string $optionName, mixed $optionValue, bool $override = true)
 * @method static mixed get(string $optionName, mixed $defaultValue = null)
 * @method static void loadAllOptions()
 * @method static int checkExists(string $optionName)
 * @method static bool update(string $optionName, mixed $optionValue, bool $autoload = false)
 * @method static mixed __call(string $name, array $args)
 *
 * @see \Ramphor\Rake\Managers\OptionManager
 */
class Option extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'option';
    }
}
