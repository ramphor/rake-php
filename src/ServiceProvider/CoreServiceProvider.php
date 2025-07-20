<?php

namespace Rake\ServiceProvider;

/**
 * Core Service Provider for Rake Framework
 *
 * Registers core framework services
 */
class CoreServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the provider's services
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // Register core services
        $this->app->singleton('config', function () {
            return [
                'framework' => [
                    'name' => 'Rake',
                    'version' => '2.0.0',
                ],
            ];
        });
    }

    /**
     * Boot the provider's services
     *
     * @return void
     */
    protected function bootServices(): void
    {
        // Boot core services if needed
    }
}