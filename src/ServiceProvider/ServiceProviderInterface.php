<?php

namespace Rake\ServiceProvider;

use Rake\Rake;

/**
 * Interface for Rake Service Providers
 *
 * Defines the contract for service provider implementations
 */
interface ServiceProviderInterface
{
    /**
     * Register services with the container
     *
     * @param Rake $app
     * @return void
     */
    public function register(Rake $app): void;

    /**
     * Boot services after all providers are registered
     *
     * @param Rake $app
     * @return void
     */
    public function boot(Rake $app): void;
}