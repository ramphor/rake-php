<?php

namespace Rake\Kernel;

use Rake\Rake;

/**
 * Interface for Rake Kernels
 *
 * Defines the contract for kernel implementations
 */
interface KernelInterface
{
    /**
     * Boot the kernel
     *
     * @return self
     */
    public function boot(): self;

    /**
     * Check if kernel is booted
     *
     * @return bool
     */
    public function isBooted(): bool;

    /**
     * Get the Rake application instance
     *
     * @return Rake
     */
    public function getApp(): Rake;

    /**
     * Get kernel status
     *
     * @return array
     */
    public function getStatus(): array;

    /**
     * Run a callback with the booted kernel
     *
     * @param callable $callback
     * @return mixed
     */
    public function run(callable $callback);
}