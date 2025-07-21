<?php

namespace Rake\Bootstrapper;

use Rake\Rake;

/**
 * Core Bootstrapper for Rake Framework
 *
 * Registers core services and configurations
 * This class is final and cannot be extended or overridden by implementations like CrawlFlow.
 */
final class CoreBootstrapper implements BootstrapperInterface
{
    /**
     * Bootstrap the core services
     *
     * @param Rake $app
     * @return void
     */
    public function bootstrap(Rake $app): void
    {
        // Register core services
        $app->singleton('config', function () {
            return [
                'framework' => [
                    'name' => 'Rake',
                    'version' => '2.0.0',
                ],
            ];
        });

        // Additional core services can be registered here
    }
}