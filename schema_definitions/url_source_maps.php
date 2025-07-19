<?php
return [
    'table' => 'rake_url_source_maps',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'url_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_urls.id',
        ],
        'source_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_data_sources.id',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['source_id']],
        ['fields' => ['url_id', 'source_id'], 'unique' => true],
    ],
    'foreign_keys' => [
        [
            'columns' => ['url_id'],
            'references' => ['table' => 'rake_urls', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
        [
            'columns' => ['source_id'],
            'references' => ['table' => 'rake_data_sources', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '2.0.0',
];