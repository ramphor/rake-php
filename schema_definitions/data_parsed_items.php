<?php
return [
    'table' => 'rake_data_parsed_items',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'origin_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_data_origins.id',
        ],
        'version' => [
            'type' => 'int',
            'default' => 1,
            'comment' => 'Version number of parsed data',
        ],
        'processor_type' => [
            'type' => 'string',
            'length' => 50,
            'comment' => 'worker, processor, extractor',
        ],
        'processor_id' => [
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'comment' => 'ID/name of processor/worker that created this version',
        ],
        'processor_name' => [
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'comment' => 'Human-readable name of processor/worker',
        ],
        'parsed_data' => [
            'type' => 'longtext',
            'comment' => 'JSON parsed data',
        ],
        'status' => [
            'type' => 'string',
            'length' => 50,
            'default' => 'pending',
            'comment' => 'pending, processed, failed, skipped',
        ],
        'has_change' => [
            'type' => 'tinyint',
            'length' => 1,
            'default' => 0,
            'comment' => 'Flag indicating if data has changed and needs saving',
        ],
        'metadata' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'JSON metadata (errors, warnings, etc.)',
        ],
        'created_at' => [
            'type' => 'datetime',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'nullable' => true,
        ],
    ],
    'indexes' => [
        ['fields' => ['origin_id', 'version'], 'unique' => true, 'name' => 'unique_origin_version'],
        ['fields' => ['origin_id']],
        ['fields' => ['version']],
        ['fields' => ['processor_type']],
        ['fields' => ['processor_id']],
        ['fields' => ['status']],
        ['fields' => ['has_change']],
        ['fields' => ['created_at']],
    ],
    'foreign_keys' => [
        [
            'columns' => ['origin_id'],
            'references' => ['table' => 'rake_data_origins', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '1.0.0',
];

