<?php
return [
    'table' => 'rake_data_origins',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'source_id' => [
            'type' => 'bigint',
            'nullable' => false,
            'comment' => 'Reference to rake_data_sources.id (NULL for child origins extracted from HTML)',
        ],
        'process_id' => [
            'type' => 'bigint',
            'nullable' => true,
            'default' => 0,
            'comment' => 'OS process id that claimed this origin',
        ],
        'guid' => [
            'type' => 'string',
            'length' => 256,
            'comment' => 'Unique identifier for origin data (url, csv row, api record, etc.)',
        ],
        'raw_data' => [
            'type' => 'longtext',
        ],
        'fetched_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
        'crawled' => [
            'type' => 'tinyint',
            'length' => 1,
            'default' => 0,
            'comment' => 'Flag indicating if URL has been crawled (has raw_data)',
        ],
        'ignored' => [
            'type' => 'tinyint',
            'length' => 1,
            'default' => 0,
            'comment' => 'Flag indicating if item should be ignored (no worker can handle it)',
        ],
        'is_archive' => [
            'type' => 'tinyint',
            'length' => 1,
            'default' => 0,
            'comment' => 'Flag indicating if this is an archive page (category pages, listing pages that contain multiple items)',
        ],
        'ignore_reason' => [
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'comment' => 'Reason for ignoring this item',
        ],
        'metadata' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'JSON metadata for extensibility',
        ],
        'source_type' => [
            'type' => 'string',
            'length' => 50,
            'nullable' => true,
            'comment' => 'data_source, processor, etc.',
        ],
        'processor_id' => [
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'comment' => 'ID/name of processor that added this URL',
        ],
        'priority' => [
            'type' => 'int',
            'default' => 100,
            'comment' => 'Priority level for processing order',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Timestamp when origin was created',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'nullable' => true,
            'comment' => 'Timestamp when origin was last updated',
        ],
    ],
    'indexes' => [
        ['fields' => ['source_id']],
        ['fields' => ['guid'], 'unique' => true],
        ['fields' => ['fetched_at']],
        ['fields' => ['created_at'], 'name' => 'idx_created_at'],
        ['fields' => ['updated_at'], 'name' => 'idx_updated_at'],
        ['fields' => ['crawled'], 'name' => 'idx_crawled'],
        ['fields' => ['ignored'], 'name' => 'idx_ignored'],
        ['fields' => ['is_archive'], 'name' => 'idx_is_archive'],
        ['fields' => ['process_id'], 'name' => 'idx_process_id'],
    ],
    'foreign_keys' => [
        [
            'columns' => ['source_id'],
            'references' => ['table' => 'rake_data_sources', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '2.4.0',
];
