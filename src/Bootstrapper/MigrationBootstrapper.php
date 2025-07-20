<?php

namespace Rake\Bootstrapper;

use Rake\Rake;

/**
 * Migration Bootstrapper for Rake Framework
 *
 * Loads migration-related services
 */
class MigrationBootstrapper implements BootstrapperInterface
{
    /**
     * Bootstrap migration services
     *
     * @param Rake $app
     * @return void
     */
    public function bootstrap(Rake $app): void
    {
        // Register migration services
        $this->registerMigrationServices($app);
    }

    /**
     * Register migration services
     *
     * @param Rake $app
     * @return void
     */
    protected function registerMigrationServices(Rake $app): void
    {
        // Register MigrationService
        $app->singleton('Rake\Admin\MigrationService', function () use ($app) {
            return new \Rake\Admin\MigrationService();
        });
    }
}