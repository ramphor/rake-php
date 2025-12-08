<?php
return [
    'table' => 'rake_file_checksums',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'resource_id' => [
            'type' => 'bigint',
        ],
        'checksum' => [
            'type' => 'string',
            'length' => 32,
        ],
        'app_new_type' => [
            'type' => 'string',
            'length' => 64,
            'nullable' => true,
            'comment' => 'Data type on target system (e.g., wordpress_post, woocommerce_product)',
        ],
        'app_new_guid' => [
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'comment' => 'Resource ID on target system (e.g., post_id, product_id)',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'on_update' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['resource_id', 'checksum'], 'unique' => true, 'name' => 'resource_checksum'],
        ['fields' => ['checksum']],
        ['fields' => ['resource_id']],
        ['fields' => ['app_new_guid']],
    ],
    'version' => '2.0.0',
];