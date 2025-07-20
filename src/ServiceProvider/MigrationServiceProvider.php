<?php

namespace Rake\ServiceProvider;

/**
 * Migration Service Provider for Rake Framework
 *
 * Registers migration-related services
 */
class MigrationServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the provider's services
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // Register migration services
        $this->app->singleton('Rake\Admin\MigrationService', function () {
            return new \Rake\Admin\MigrationService();
        });
    }

    /**
     * Boot the provider's services
     *
     * @return void
     */
    protected function bootServices(): void
    {
        // Boot migration services if needed
    }
}