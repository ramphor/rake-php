<?php

namespace Rake\Config;

/**
 * Database Configuration Manager
 */
class DatabaseConfig
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var array
     */
    private $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? '';
    }

    /**
     * Set database prefix
     *
     * @param string $prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        $this->config['prefix'] = $prefix;
        return $this;
    }

    /**
     * Get database prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get full table name with prefix
     *
     * @param string $tableName
     * @return string
     */
    public function getTableName(string $tableName): string
    {
        return $this->prefix . $tableName;
    }

    /**
     * Get database configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get database driver
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->config['driver'] ?? 'mysql';
    }

    /**
     * Get database host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->config['host'] ?? 'localhost';
    }

    /**
     * Get database port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->config['port'] ?? 3306;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->config['dbname'] ?? '';
    }

    /**
     * Get database username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->config['user'] ?? '';
    }

    /**
     * Get database password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->config['password'] ?? '';
    }

    /**
     * Get database charset
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->config['charset'] ?? 'utf8mb4';
    }

    /**
     * Get database collation
     *
     * @return string
     */
    public function getCollation(): string
    {
        return $this->config['collation'] ?? 'utf8mb4_unicode_ci';
    }

    /**
     * Check if configuration is valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->config['dbname']) &&
               !empty($this->config['user']) &&
               !empty($this->config['password']);
    }

    /**
     * Get connection string for PDO
     *
     * @return string
     */
    public function getConnectionString(): string
    {
        $driver = $this->getDriver();
        $host = $this->getHost();
        $port = $this->getPort();
        $database = $this->getDatabase();
        $charset = $this->getCharset();

        return "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
    }
}