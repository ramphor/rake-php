<?php

namespace Rake\Kernel;

use Rake\Rake;

/**
 * Console Kernel for Rake Framework
 *
 * Handles console tasks such as Cron and Batch operations
 */
class ConsoleKernel extends AbstractKernel
{
    /**
     * Register custom bootstrappers for console kernel
     *
     * @return void
     */
    protected function registerBootstrappers(): void
    {
        // Add console-specific bootstrappers if needed
        // $this->addCustomBootstrapper(\Rake\Bootstrapper\ConsoleBootstrapper::class);
    }

    /**
     * Initialize console kernel
     *
     * @return self
     */
    public function initialize(): self
    {
        $this->setConfig([
            'kernel' => 'console',
            'logging' => [
                'level' => 'info',
                'file' => 'rake-console.log',
                'directory' => WP_CONTENT_DIR . '/rake/',
            ],
        ]);
        return $this;
    }

    /**
     * Run a console command
     *
     * @param string $command
     * @param array $arguments
     * @return mixed
     */
    public function runCommand(string $command, array $arguments = [])
    {
        $this->boot();
        // Logic to execute console command
        return $this->app->make('console')->execute($command, $arguments);
    }
}