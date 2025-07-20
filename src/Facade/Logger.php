<?php

namespace Rake\Facade;

use Rake\Manager\LoggerManager;

/**
 * Logger Facade for Rake Framework
 *
 * Provides a simple interface to the LoggerManager through Rake container
 *
 * Available methods:
 *
 * PSR-3 Log Levels:
 * - static debug(string $message, array $context = []): void
 * - static info(string $message, array $context = []): void
 * - static notice(string $message, array $context = []): void
 * - static warning(string $message, array $context = []): void
 * - static error(string $message, array $context = []): void
 * - static critical(string $message, array $context = []): void
 * - static alert(string $message, array $context = []): void
 * - static emergency(string $message, array $context = []): void
 *
 * Output Buffering:
 * - static startBuffer(): void
 * - static stopBuffer(): void
 * - static getBuffer(): string
 *
 * Logger Management:
 * - static setLogger(LoggerInterface $logger): void
 *
 * Domain Specific Methods:
 * - static databaseInfo(string $message, array $context = []): void
 * - static databaseError(string $message, array $context = []): void
 * - static migrationInfo(string $message, array $context = []): void
 * - static migrationError(string $message, array $context = []): void
 * - static migrationSuccess(string $message, array $context = []): void
 *
 * Usage Examples:
 * Logger::info('Application started');
 * Logger::error('Database connection failed', ['host' => 'localhost']);
 * Logger::startBuffer();
 * // ... do something that might output
 * Logger::stopBuffer();
 */
class Logger extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggerManager::class;
    }

    /**
     * Get logger instance with fallback
     */
    private static function getLoggerInstance()
    {
        try {
            return static::getFacadeRoot();
        } catch (\Exception $e) {
            // Fallback to error_log if container is not available
            return null;
        }
    }

    /**
     * Set the underlying logger
     */
    public static function setLogger($logger): void
    {
        try {
            static::getFacadeRoot()->setLogger($logger);
        } catch (\Exception $e) {
            error_log('Logger Facade: Failed to set logger - ' . $e->getMessage());
        }
    }

    /**
     * Start output buffering
     */
    public static function startBuffer(): void
    {
        try {
            static::getFacadeRoot()->startBuffer();
        } catch (\Exception $e) {
            // Silently fail if container is not available
        }
    }

    /**
     * Stop output buffering
     */
    public static function stopBuffer(): void
    {
        try {
            static::getFacadeRoot()->stopBuffer();
        } catch (\Exception $e) {
            // Silently fail if container is not available
        }
    }

    /**
     * Get buffered output
     */
    public static function getBuffer(): string
    {
        try {
            return static::getFacadeRoot()->getBuffer();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Log message with fallback
     */
    private static function logMessage(string $level, string $message, array $context = []): void
    {
        $logger = self::getLoggerInstance();

        if ($logger) {
            $logger->log($level, $message, $context);
        } else {
            // Fallback to error_log
            $timestamp = date('Y-m-d H:i:s');
            $formattedMessage = sprintf('[%s] %s: %s', $timestamp, strtoupper($level), $message);

            if (!empty($context)) {
                $formattedMessage .= ' ' . json_encode($context);
            }

            error_log($formattedMessage);
        }
    }

    /**
     * Log methods with fallback
     */
    public static function debug(string $message, array $context = []): void
    {
        self::logMessage('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::logMessage('info', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::logMessage('notice', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::logMessage('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::logMessage('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::logMessage('critical', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::logMessage('alert', $message, $context);
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::logMessage('emergency', $message, $context);
    }

    /**
     * Domain specific logging methods
     */
    public static function databaseInfo(string $message, array $context = []): void
    {
        self::logMessage('info', "Database: {$message}", $context);
    }

    public static function databaseError(string $message, array $context = []): void
    {
        self::logMessage('error', "Database: {$message}", $context);
    }

    public static function migrationInfo(string $message, array $context = []): void
    {
        self::logMessage('info', "Migration: {$message}", $context);
    }

    public static function migrationError(string $message, array $context = []): void
    {
        self::logMessage('error', "Migration: {$message}", $context);
    }

    public static function migrationSuccess(string $message, array $context = []): void
    {
        self::logMessage('info', "Migration Success: {$message}", $context);
    }
}
