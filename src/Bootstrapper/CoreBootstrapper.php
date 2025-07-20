<?php

namespace Rake\Bootstrapper;

use Rake\Rake;

/**
 * Core Bootstrapper for Rake Framework
 *
 * Loads core system components
 */
class CoreBootstrapper implements BootstrapperInterface
{
    /**
     * Bootstrap core services
     *
     * @param Rake $app
     * @return void
     */
    public function bootstrap(Rake $app): void
    {
        // Register core services
        $this->registerCoreServices($app);
    }

    /**
     * Register core services
     *
     * @param Rake $app
     * @return void
     */
    protected function registerCoreServices(Rake $app): void
    {
        // Register configuration
        $app->singleton('config', function () {
            return [
                'framework' => [
                    'name' => 'Rake',
                    'version' => '2.0.0',
                ],
            ];
        });
    }
}