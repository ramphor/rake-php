<?php

namespace Rake;

/**
 * Application Bootstrapper
 * Centralized bootstrapper for registering and booting service providers
 * 
 * This class provides a generic way to bootstrap any Rake-based application
 * by managing service provider registration and booting lifecycle
 */
class ApplicationBootstrapper
{
    /**
     * @var Rake Application instance
     */
    private Rake $app;

    /**
     * @var array Service providers to register
     */
    private array $providers = [];

    /**
     * @var array Registered provider instances
     */
    private array $registeredProviders = [];

    /**
     * @var bool Bootstrap status
     */
    private bool $bootstrapped = false;

    /**
     * Constructor
     * 
     * @param Rake|null $app Rake application instance (optional, will create new if not provided)
     * @param array $providers Service provider class names
     */
    public function __construct(?Rake $app = null, array $providers = [])
    {
        $this->app = $app ?? Rake::getInstance();
        $this->providers = $providers;
    }

    /**
     * Bootstrap the application
     * Registers and boots all service providers
     * 
     * @return self
     * @throws \RuntimeException If bootstrapping fails
     */
    public function bootstrap(): self
    {
        if ($this->bootstrapped) {
            return $this;
        }

        $this->registerProviders();
        $this->bootProviders();
        
        $this->bootstrapped = true;
        
        return $this;
    }

    /**
     * Register all service providers
     * 
     * @throws \RuntimeException If provider registration fails
     */
    private function registerProviders(): void
    {
        foreach ($this->providers as $providerClass) {
            $this->registerProvider($providerClass);
        }
    }

    /**
     * Register a single service provider
     * 
     * @param string $providerClass Service provider class name
     * @throws \RuntimeException If provider class doesn't exist
     */
    private function registerProvider(string $providerClass): void
    {
        if (!class_exists($providerClass)) {
            throw new \RuntimeException("Service provider not found: {$providerClass}");
        }

        $provider = new $providerClass();
        
        // Register services if provider has register method
        if (method_exists($provider, 'register')) {
            $provider->register($this->app);
        }

        $this->registeredProviders[] = $provider;
    }

    /**
     * Boot all registered service providers
     */
    private function bootProviders(): void
    {
        foreach ($this->registeredProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot($this->app);
            }
        }
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
     * Get registered providers
     * 
     * @return array
     */
    public function getRegisteredProviders(): array
    {
        return $this->registeredProviders;
    }

    /**
     * Add custom provider
     * Can be called before bootstrap() to add additional providers
     * 
     * @param string $providerClass Service provider class name
     * @return self
     */
    public function addProvider(string $providerClass): self
    {
        if (!in_array($providerClass, $this->providers)) {
            $this->providers[] = $providerClass;
        }
        
        return $this;
    }

    /**
     * Set providers (replaces existing)
     * 
     * @param array $providers Service provider class names
     * @return self
     */
    public function setProviders(array $providers): self
    {
        $this->providers = $providers;
        return $this;
    }

    /**
     * Get providers list
     * 
     * @return array
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Check if bootstrapped
     * 
     * @return bool
     */
    public function isBootstrapped(): bool
    {
        return $this->bootstrapped;
    }

    /**
     * Reset bootstrapper (for testing)
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->registeredProviders = [];
        $this->bootstrapped = false;
        
        return $this;
    }
}

