<?php
namespace Ramphor\Rake\Facades;

use Ramphor\Rake\Rake;

abstract class Facade
{
    protected static $rake;

    /**
     * Set the application instance
     *
     * @param \Ramphor\Rake\Rake $rake
     * @return void
     */
    public static function setRakeApplication(Rake $rake)
    {
        static::$rake = $rake;
    }
}
