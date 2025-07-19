<?php
return [
    'table' => 'rake_queues',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'queue' => [
            'type' => 'string',
            'length' => 255,
            'default' => 'default',
            'comment' => 'Queue name',
        ],
        'payload' => [
            'type' => 'longtext',
            'comment' => 'Serialized job data',
        ],
        'attempts' => [
            'type' => 'tinyint',
            'default' => 0,
            'comment' => 'Number of attempts',
        ],
        'reserved_at' => [
            'type' => 'int',
            'nullable' => true,
            'comment' => 'Timestamp when job was reserved',
        ],
        'available_at' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Timestamp when job is available',
        ],
        'created_at' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Timestamp when job was created',
        ],
    ],
    'indexes' => [
        ['fields' => ['queue']],
        ['fields' => ['reserved_at']],
        ['fields' => ['available_at']],
    ],
    'version' => '2.0.0',
];