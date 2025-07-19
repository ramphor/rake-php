<?php
return [
    'table' => 'rake_tooths',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
            'comment' => 'Auto-increment ID',
        ],
        'name' => [
            'type' => 'string',
            'length' => 128,
            'comment' => 'Tooth/project name',
        ],
        'description' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'Tooth/project description',
        ],
        'config' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'Tooth configuration (JSON)',
        ],
        'status' => [
            'type' => 'string',
            'length' => 32,
            'default' => 'active',
            'comment' => 'Tooth status (active, inactive, archived, ...)',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Tooth creation timestamp',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'nullable' => true,
            'comment' => 'Last tooth update timestamp',
        ],
    ],
    'indexes' => [
        ['fields' => ['name']],
        ['fields' => ['status']],
    ],
    'version' => '1.0.0',
];