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
            'type' => 'longtext',
            'nullable' => true,
            'comment' => 'Tooth configuration (JSON)',
        ],
        'status' => [
            'type' => 'string',
            'length' => 32,
            'default' => 'active',
            'comment' => 'Tooth status (active, inactive, archived, ...)',
        ],
        'metadata' => [
            'type' => 'longtext',
            'nullable' => true,
            'comment' => 'Tooth configuration (JSON)',
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
        ['fields' => ['completion_percentage']],
        ['fields' => ['last_status_update']],
        ['fields' => ['created_at']],
    ],
    'constraints' => [
        [
            'name' => 'chk_completion_percentage',
            'type' => 'check',
            'constraint' => 'completion_percentage >= 0.00 AND completion_percentage <= 100.00',
            'comment' => 'Ensure completion percentage is within valid range',
        ],
        [
            'name' => 'chk_status_values',
            'type' => 'check',
            'constraint' => "status IN ('draft', 'active', 'running', 'completed', 'failed', 'partial', 'paused', 'cancelled')",
            'comment' => 'Ensure status is one of the allowed values',
        ],
    ],
    'version' => '2.0.0',
];