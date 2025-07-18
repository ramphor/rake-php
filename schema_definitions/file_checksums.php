<?php
return [
    'table' => 'rake_file_checksums',
    'fields' => [
        'id' => [
            'type' => 'int',
            'auto_increment' => true,
            'primary' => true,
        ],
        'resource_id' => [
            'type' => 'int',
        ],
        'checksum' => [
            'type' => 'string',
            'length' => 128,
        ],
        'app_new_type' => [
            'type' => 'string',
            'length' => 64,
            'nullable' => true,
            'comment' => 'Data type on target system (e.g., wordpress_post, woocommerce_product)',
        ],
        'app_new_guid' => [
            'type' => 'string',
            'length' => 128,
            'nullable' => true,
            'comment' => 'Resource ID on target system (e.g., post_id, product_id)',
        ],

        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['resource_id']],
        ['fields' => ['checksum']],
        ['fields' => ['app_new_type']],
        ['fields' => ['app_new_guid']],
    ],
    'version' => '2.0.0',
];