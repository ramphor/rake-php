<?php

namespace Rake\Kernel;

use Rake\Rake;

/**
 * Abstract Kernel for Rake Framework
 *
 * Provides base functionality for kernel implementations
 */
abstract class AbstractKernel implements KernelInterface
{
    /**
     * @var Rake
     */
    protected $app;

    /**
     * @var array
     */
    protected $bootstrappers = [];

    /**
     * @var array
     */
    protected $defaultBootstrappers = [];

    /**
     * @var array
     */
    protected $customBootstrappers = [];

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = Rake::getInstance();
        $this->registerDefaultBootstrappers();
        $this->registerBootstrappers();
    }

    /**
     * Register default bootstrappers
     *
     * @return void
     */
    protected function registerDefaultBootstrappers(): void
    {
        $this->defaultBootstrappers = [
            \Rake\Bootstrapper\CoreBootstrapper::class,
        ];

        foreach ($this->defaultBootstrappers as $bootstrapper) {
            $this->addBootstrapper($bootstrapper);
        }
    }

    /**
     * Register custom bootstrappers (to be implemented by child classes)
     *
     * @return void
     */
    abstract protected function registerBootstrappers(): void;

    /**
     * Add bootstrapper to the custom list
     *
     * @param string $bootstrapper
     * @return self
     */
    protected function addCustomBootstrapper(string $bootstrapper): self
    {
        if (!in_array($bootstrapper, $this->customBootstrappers)) {
            $this->customBootstrappers[] = $bootstrapper;
        }
        $this->addBootstrapper($bootstrapper);
        return $this;
    }

    /**
     * Add bootstrapper to the main list
     *
     * @param string $bootstrapper
     * @return self
     */
    protected function addBootstrapper(string $bootstrapper): self
    {
        if (!in_array($bootstrapper, $this->bootstrappers)) {
            $this->bootstrappers[] = $bootstrapper;
        }
        return $this;
    }

    /**
     * Get all registered bootstrappers
     *
     * @return array
     */
    public function getBootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Boot the kernel
     *
     * @return self
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        $this->runBootstrappers();
        $this->booted = true;
        return $this;
    }

    /**
     * Run all registered bootstrappers
     *
     * @return void
     */
    protected function runBootstrappers(): void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->runBootstrapper($bootstrapper);
        }
    }

    /**
     * Run a single bootstrapper
     *
     * @param string $bootstrapper
     * @return void
     */
    protected function runBootstrapper(string $bootstrapper): void
    {
        if (!class_exists($bootstrapper)) {
            throw new \RuntimeException("Bootstrapper class not found: {$bootstrapper}");
        }

        $instance = new $bootstrapper();
        $instance->bootstrap($this->app);
    }

    /**
     * Check if kernel is booted
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get Rake application instance
     *
     * @return Rake
     */
    public function getApp(): Rake
    {
        return $this->app;
    }

    /**
     * Get kernel status
     *
     * @return array
     */
    public function getStatus(): array
    {
        return [
            'booted' => $this->booted,
            'bootstrappers' => $this->bootstrappers,
            'config' => $this->config,
        ];
    }

    /**
     * Run a callback with the booted kernel
     *
     * @param callable $callback
     * @return mixed
     */
    public function run(callable $callback)
    {
        $this->boot();
        return $callback($this->app);
    }

    /**
     * Set configuration
     *
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get configuration
     *
     * @param string|null $key
     * @return mixed
     */
    public function getConfig(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
}