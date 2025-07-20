<?php

namespace Rake\ServiceProvider;

/**
 * Console Service Provider for Rake Framework
 *
 * Registers console-related services
 */
class ConsoleServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the provider's services
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // Register console services
        // $this->app->singleton('Rake\Console\ConsoleService', function () {
        //     return new \Rake\Console\ConsoleService();
        // });
    }

    /**
     * Boot the provider's services
     *
     * @return void
     */
    protected function bootServices(): void
    {
        // Boot console services if needed
    }
}