<?php
return [
    'table' => 'rake_configs',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'key' => [
            'type' => 'string',
            'length' => 128,
            'primary' => true,
        ],
        'value' => [
            'type' => 'text',
            'nullable' => true,
        ],
        'updated_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'version' => '2.0.0',
];