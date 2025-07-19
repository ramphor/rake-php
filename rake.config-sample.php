<?php

return [
    // Database configuration
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'your_database',
        'user' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'rake_', // Database table prefix
    ],

    // HTTP Client configuration
    'http_client' => [
        'timeout' => 30,
        'user_agent' => 'Rake/2.0',
        'retries' => 3,
        'delay' => 1, // Delay between requests in seconds
    ],

    // Logging configuration
    'logging' => [
        'level' => 'info', // debug, info, warning, error
        'file' => 'rake.log',
        'max_files' => 10,
    ],

    // Migration configuration
    'migration' => [
        'schema_dir' => __DIR__ . '/schema_definitions',
        'backup_tables' => true,
        'dry_run' => false, // Set to true to preview changes without executing
    ],

    // Resource management
    'resources' => [
        'max_concurrent_downloads' => 5,
        'download_timeout' => 60,
        'storage_path' => __DIR__ . '/storage',
    ],

    // Processing configuration
    'processing' => [
        'batch_size' => 100,
        'memory_limit' => '256M',
        'max_execution_time' => 300,
    ],
];
