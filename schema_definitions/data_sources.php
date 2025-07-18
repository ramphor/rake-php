<?php
return [
    'table' => 'rake_data_sources',
    'fields' => [
        'id' => [
            'type' => 'int',
            'auto_increment' => true,
            'primary' => true,
        ],
        'tooth_id' => [
            'type' => 'int',
            'comment' => 'Reference to rake_tooths.id (project/tooth owner)',
        ],
        'type' => [
            'type' => 'string',
            'length' => 32,
            'comment' => 'Source type: url, csv, api, manual, ...',
        ],
        'name' => [
            'type' => 'string',
            'length' => 128,
            'comment' => 'Source name (file, endpoint, ...)',
        ],
        'config' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'Crawl/migrate configuration (JSON)',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['tooth_id']],
        ['fields' => ['type']],
        ['fields' => ['name']],
    ],
    'foreign_keys' => [
        [
            'columns' => ['tooth_id'],
            'references' => ['table' => 'rake_tooths', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '2.0.0',
];