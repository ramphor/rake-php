<?php

namespace Rake\Manager;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logger Manager for Rake Framework
 *
 * Generic logging manager that wraps PSR-3 LoggerInterface
 */
class LoggerManager
{
    private static $instance = null;
    private $logger;
    private $outputBuffer = '';
    private $isBuffering = false;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the underlying logger
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the underlying logger
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Start output buffering
     */
    public function startBuffer(): self
    {
        $this->isBuffering = true;
        $this->outputBuffer = '';
        ob_start();
        return $this;
    }

    /**
     * Stop output buffering and clear buffer
     */
    public function stopBuffer(): self
    {
        if ($this->isBuffering) {
            ob_end_clean();
            $this->isBuffering = false;
            $this->outputBuffer = '';
        }
        return $this;
    }

    /**
     * Get buffered output
     */
    public function getBuffer(): string
    {
        return $this->outputBuffer;
    }

    /**
     * Log message with level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        } else {
            // Fallback to error_log if no logger is set
            $timestamp = date('Y-m-d H:i:s');
            $formattedMessage = sprintf(
                '[%s] %s: %s',
                $timestamp,
                strtoupper($level),
                $message
            );

            if (!empty($context)) {
                $formattedMessage .= ' ' . json_encode($context);
            }

            error_log($formattedMessage);
        }

        // Add to buffer if buffering
        if ($this->isBuffering) {
            $this->outputBuffer .= $formattedMessage . PHP_EOL;
        }
    }

    /**
     * Convenience methods for different log levels
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Domain specific logging methods
     */
    public function databaseInfo(string $message, array $context = []): void
    {
        $this->info("Database: {$message}", $context);
    }

    public function databaseError(string $message, array $context = []): void
    {
        $this->error("Database: {$message}", $context);
    }

    public function migrationInfo(string $message, array $context = []): void
    {
        $this->info("Migration: {$message}", $context);
    }

    public function migrationError(string $message, array $context = []): void
    {
        $this->error("Migration: {$message}", $context);
    }

    public function migrationSuccess(string $message, array $context = []): void
    {
        $this->info("Migration Success: {$message}", $context);
    }
}