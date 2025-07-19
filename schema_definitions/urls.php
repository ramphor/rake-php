<?php
return [
    'table' => 'rake_urls',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'tooth_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_tooths.id (project/tooth owner)',
        ],
        'url' => [
            'type' => 'string',
            'length' => 191,
        ],
        'status' => [
            'type' => 'string',
            'length' => 32,
            'default' => 'pending',
            'comment' => 'pending, processing, done, failed, skipped, ...',
        ],
        'skipped' => [
            'type' => 'tinyint',
            'default' => 0,
            'comment' => '1 if skipped, 0 otherwise',
        ],
        'retry_count' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of retry attempts',
        ],
        'last_error' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'Last error message if failed',
        ],
        'crawled_at' => [
            'type' => 'datetime',
            'nullable' => true,
            'comment' => 'Timestamp when URL was crawled',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['tooth_id']],
        ['fields' => ['url']],
        ['fields' => ['status']],
        ['fields' => ['skipped']],
    ],
    'foreign_keys' => [
        [
            'columns' => ['tooth_id'],
            'references' => ['table' => 'rake_tooths', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => 2,
];