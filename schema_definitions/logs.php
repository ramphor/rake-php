<?php
return [
    'table' => 'rake_logs',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'int',
            'auto_increment' => true,
            'primary' => true,
        ],
        'level' => [
            'type' => 'string',
            'length' => 16,
            'default' => 'info',
        ],
        'message' => [
            'type' => 'text',
        ],
        'context' => [
            'type' => 'text',
            'nullable' => true,
        ],
        'resource_id' => [
            'type' => 'int',
            'nullable' => true,
        ],
        'url_id' => [
            'type' => 'int',
            'nullable' => true,
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['level']],
        ['fields' => ['resource_id']],
        ['fields' => ['url_id']],
    ],
    'version' => '2.0.0',
];