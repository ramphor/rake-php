<?php

namespace Rake\Bootstrapper;

use Rake\Rake;

/**
 * Interface for Rake Bootstrappers
 *
 * Defines the contract for bootstrapper implementations
 */
interface BootstrapperInterface
{
    /**
     * Bootstrap the application with necessary services
     *
     * @param Rake $app
     * @return void
     */
    public function bootstrap(Rake $app): void;
}