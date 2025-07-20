<?php

namespace Rake\Kernel;

use Rake\Rake;

/**
 * Migration Kernel for Rake Framework
 *
 * Handles database migration tasks
 */
class MigrationKernel extends AbstractKernel
{
    /**
     * @var array
     */
    protected $migrationResults = [];

    /**
     * @var array
     */
    protected $migrationErrors = [];

    /**
     * Register custom bootstrappers for migration kernel
     *
     * @return void
     */
    protected function registerBootstrappers(): void
    {
        $this->addCustomBootstrapper(\Rake\Bootstrapper\MigrationBootstrapper::class);
    }

    /**
     * Initialize migration kernel
     *
     * @return self
     */
    public function initializeMigration(): self
    {
        $this->setConfig([
            'kernel' => 'migration',
            'migration' => [
                'enabled' => true,
                'auto_run' => true,
                'backup_before' => true,
                'rollback_on_error' => true,
                'batch_size' => 10,
            ],
            'database' => [
                'driver' => 'wordpress',
                'prefix' => $this->getWordPressPrefix(),
                'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
                'collate' => defined('DB_COLLATE') ? DB_COLLATE : 'utf8mb4_unicode_ci',
            ],
            'logging' => [
                'level' => 'info',
                'file' => 'rake-migration.log',
                'directory' => WP_CONTENT_DIR . '/rake/logs/',
            ],
        ]);
        return $this->boot();
    }

    /**
     * Get WordPress database prefix
     *
     * @return string
     */
    protected function getWordPressPrefix(): string
    {
        global $wpdb;
        return $wpdb->prefix ?? 'wp_';
    }

    /**
     * Run migrations
     *
     * @param array $options
     * @return array
     */
    public function runMigrations(array $options = []): array
    {
        try {
            $app = $this->getApp();
            $migrationService = $app->make('Rake\Admin\MigrationService');

            $migrationOptions = array_merge(
                $this->getConfig('migration') ?? [],
                $options
            );

            $result = $migrationService->runMigrations($migrationOptions);
            $this->migrationResults = $result;
            return $result;
        } catch (\Exception $e) {
            $this->migrationErrors[] = $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->migrationErrors
            ];
        }
    }

    /**
     * Check migration status
     *
     * @return array
     */
    public function checkMigrationStatus(): array
    {
        try {
            $app = $this->getApp();
            $migrationService = $app->make('Rake\Admin\MigrationService');
            return $migrationService->getMigrationStatus();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rollback migrations
     *
     * @param int $steps
     * @return array
     */
    public function rollbackMigrations(int $steps = 1): array
    {
        try {
            $app = $this->getApp();
            $migrationService = $app->make('Rake\Admin\MigrationService');
            return $migrationService->rollbackMigrations($steps);
        } catch (\Exception $e) {
            $this->migrationErrors[] = $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->migrationErrors
            ];
        }
    }

    /**
     * Get migration results
     *
     * @return array
     */
    public function getMigrationResults(): array
    {
        return $this->migrationResults;
    }

    /**
     * Get migration errors
     *
     * @return array
     */
    public function getMigrationErrors(): array
    {
        return $this->migrationErrors;
    }
}