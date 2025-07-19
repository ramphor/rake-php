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
            'comment' => 'Reference to rake_data_sources.id',
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
    ],
    'indexes' => [
        ['fields' => ['source_id']],
        ['fields' => ['guid'], 'unique' => true],
    ],
    'foreign_keys' => [
        [
            'columns' => ['source_id'],
            'references' => ['table' => 'rake_data_sources', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '2.0.0',
];
