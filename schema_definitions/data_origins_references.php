<?php
return [
    'table' => 'rake_data_origins_references',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'parent_origin_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_data_origins.id (parent)',
        ],
        'child_origin_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_data_origins.id (child)',
        ],
        'relationship_type' => [
            'type' => 'string',
            'length' => 50,
            'default' => 'parent_child',
            'comment' => 'parent_child, category_product, etc.',
        ],
        'created_at' => [
            'type' => 'datetime',
        ],
    ],
    'indexes' => [
        ['fields' => ['parent_origin_id', 'child_origin_id'], 'unique' => true, 'name' => 'unique_parent_child_origin'],
        ['fields' => ['parent_origin_id']],
        ['fields' => ['child_origin_id']],
        ['fields' => ['relationship_type']],
    ],
    'foreign_keys' => [
        [
            'columns' => ['parent_origin_id'],
            'references' => ['table' => 'rake_data_origins', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
        [
            'columns' => ['child_origin_id'],
            'references' => ['table' => 'rake_data_origins', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '1.0.0',
];

