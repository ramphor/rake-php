<?php

namespace Rake\Kernel;

use Rake\Bootstrapper\MigrationBootstrapper;

/**
 * Migration Kernel for Rake Framework
 *
 * Handles migration tasks such as up and down operations
 */
class MigrationKernel extends AbstractKernel
{
    protected function registerBootstrappers(): void
    {
        $this->addCustomBootstrapper(MigrationBootstrapper::class);
    }

    /**
     * Get the bootstrappers for this kernel
     *
     * @return array<BootstrapperInterface>
     */
    public function getBootstrappers(): array
    {
        return [
            new MigrationBootstrapper(),
        ];
    }

    /**
     * Handle the kernel's primary task
     *
     * @return void
     */
    public function handle(): void
    {
        // Handle migration tasks
        $migrationService = $this->app->make('Rake\Admin\MigrationService');
        $migrationService->executeMigrations();
    }

    /**
     * Get the database prefix for migrations
     *
     * @return string
     */
    protected function getDatabasePrefix(): string
    {
        return 'rake_'; // Default prefix for Rake framework, can be overridden in configuration
    }
}