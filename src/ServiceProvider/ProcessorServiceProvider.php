<?php

namespace Rake\ServiceProvider;

use Rake\Manager\ProcessorManager;
use Rake\Processor\SaveToDatabaseProcessor;
use Rake\Processor\SendToApiProcessor;
use Rake\Processor\GenerateCsvFileProcessor;
use Rake\Processor\SendEmailNotificationProcessor;

/**
 * Processor Service Provider
 * Registers built-in Rake processors
 */
class ProcessorServiceProvider extends AbstractServiceProvider
{
    /**
     * Register processor services
     */
    protected function registerServices(): void
    {
        // Register ProcessorManager singleton
        $this->app->singleton(ProcessorManager::class, function ($app) {
            return new ProcessorManager();
        });

        // Register built-in processors
        $this->registerBuiltInProcessors();
    }

    /**
     * Boot processor services
     */
    protected function bootServices(): void
    {
        // Nothing to boot
    }

    /**
     * Register built-in Rake processors
     */
    private function registerBuiltInProcessors(): void
    {
        // 1. Save to Database Processor
        ProcessorManager::register(
            'save_to_database',
            SaveToDatabaseProcessor::class,
            [
                'connectionType' => 'mysql',
                'host' => 'localhost',
                'port' => '3306',
                'user' => 'root',
                'password' => '',
                'database' => 'scraped_data',
                'tableName' => 'results',
                'conflictStrategy' => 'upsert',
            ]
        );

        // Aliases
        ProcessorManager::alias('save_to_db', 'save_to_database');
        ProcessorManager::alias('database', 'save_to_database');

        // 2. Send to API Processor
        ProcessorManager::register(
            'send_to_api',
            SendToApiProcessor::class,
            [
                'endpointUrl' => 'https://api.example.com/data',
                'method' => 'POST',
                'authType' => 'none',
                'authDetails' => [],
                'headers' => [],
            ]
        );

        // Aliases
        ProcessorManager::alias('api', 'send_to_api');
        ProcessorManager::alias('webhook', 'send_to_api');

        // 3. Generate CSV File Processor
        ProcessorManager::register(
            'generate_csv_file',
            GenerateCsvFileProcessor::class,
            [
                'fileName' => 'crawl_results_{{date}}.csv',
                'delimiter' => ',',
                'includeHeader' => true,
            ]
        );

        // Aliases
        ProcessorManager::alias('csv', 'generate_csv_file');
        ProcessorManager::alias('export_csv', 'generate_csv_file');

        // 4. Send Email Notification Processor
        ProcessorManager::register(
            'send_email_notification',
            SendEmailNotificationProcessor::class,
            [
                'recipients' => 'admin@example.com',
                'subject' => 'Crawl Finished: New Data Found',
                'body' => 'Data was successfully extracted.',
            ]
        );

        // Aliases
        ProcessorManager::alias('email', 'send_email_notification');
        ProcessorManager::alias('notify', 'send_email_notification');

        // Log registration
        if (function_exists('error_log')) {
            error_log('Rake: Built-in processors registered in ProcessorManager');
        }
    }
}

