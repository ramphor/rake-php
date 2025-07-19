<?php
return [
    'table' => 'rake_configs',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'config_key' => [
            'type' => 'string',
            'length' => 128,
            'primary' => true,
        ],
        'config_value' => [
            'type' => 'text',
            'nullable' => true,
        ],
        'updated_at' => [
            'type' => 'datetime',
            // Bỏ default
        ],
    ],
    'version' => '2.0.0',
];