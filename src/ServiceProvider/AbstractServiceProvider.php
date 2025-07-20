<?php

namespace Rake\ServiceProvider;

use Rake\Rake;

/**
 * Abstract base class for Rake Service Providers
 *
 * Provides common functionality for service providers
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    /**
     * The application instance
     *
     * @var Rake
     */
    protected $app;

    /**
     * Register services with the container
     *
     * @param Rake $app
     * @return void
     */
    public function register(Rake $app): void
    {
        $this->app = $app;
        $this->registerServices();
    }

    /**
     * Boot services after all providers are registered
     *
     * @param Rake $app
     * @return void
     */
    public function boot(Rake $app): void
    {
        $this->app = $app;
        $this->bootServices();
    }

    /**
     * Register the provider's services
     *
     * @return void
     */
    abstract protected function registerServices(): void;

    /**
     * Boot the provider's services
     *
     * @return void
     */
    abstract protected function bootServices(): void;
}