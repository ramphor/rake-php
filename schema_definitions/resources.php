<?php
return [
    'table' => 'rake_resources',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'int',
            'auto_increment' => true,
            'primary' => true,
            'comment' => 'Auto-increment ID',
        ],
        'parent_id' => [
            'type' => 'int',
            'nullable' => true,
            'comment' => 'Parent resource ID (if any)',
        ],
        'tooth_id' => [
            'type' => 'int',
            'nullable' => true,
            'comment' => 'Tooth (project) ID that owns this resource',
        ],
        'data_type' => [
            'type' => 'string',
            'length' => 64,
            'comment' => 'Data type of resource (image, post, product, ...)',
        ],
        'guid' => [
            'type' => 'string',
            'length' => 512,
            'comment' => 'Unique identifier for resource (string, id, hash, ...)',
        ],
        'current_content' => [
            'type' => 'longtext',
            'nullable' => true,
            'comment' => 'Current content of resource (used for update, sync)',
        ],
        'app_data_type' => [
            'type' => 'string',
            'length' => 64,
            'nullable' => true,
            'comment' => 'Data type on current system (e.g., wordpress_post, woocommerce_product, ...)',
        ],
        'app_guid' => [
            'type' => 'string',
            'length' => 128,
            'nullable' => true,
            'comment' => 'Resource ID on current system (e.g., post_id, product_id, ...)',
        ],
        'import_status' => [
            'type' => 'string',
            'length' => 32,
            'default' => 'pending',
            'comment' => 'Import status (pending, imported, failed, placeholder, ...)',
        ],
        'import_retry' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of import retry attempts',
        ],
        'imported_at' => [
            'type' => 'datetime',
            'nullable' => true,
            'comment' => 'Timestamp when resource was successfully imported',
        ],
        'metadata' => [
            'type' => 'text',
            'nullable' => true,
            'comment' => 'Extended metadata (JSON format, stores auxiliary information, dynamic data, ...)',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Resource creation timestamp',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'nullable' => true,
            'comment' => 'Last resource update timestamp',
        ],
    ],
    'indexes' => [
        ['fields' => ['parent_id']],
        ['fields' => ['tooth_id']],
        ['fields' => ['guid'], 'unique' => true],
        ['fields' => ['data_type']],
        ['fields' => ['app_data_type']],
        ['fields' => ['app_guid']],
        ['fields' => ['import_status']],
    ],
    'foreign_keys' => [
        [
            'name' => 'fk_resources_parent',
            'columns' => ['parent_id'],
            'references' => ['table' => 'rake_resources', 'columns' => ['id']],
            'on_delete' => 'SET NULL',
            'on_update' => 'CASCADE',
        ],
        [
            'name' => 'fk_resources_tooth',
            'columns' => ['tooth_id'],
            'references' => ['table' => 'rake_tooths', 'columns' => ['id']],
            'on_delete' => 'SET NULL',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '2.0.0',
];