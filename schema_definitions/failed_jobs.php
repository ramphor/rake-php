<?php
return [
    'table' => 'rake_failed_jobs',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'uuid' => [
            'type' => 'string',
            'length' => 255,
            'comment' => 'Unique job UUID',
        ],
        'connection' => [
            'type' => 'text',
            'comment' => 'Queue connection name',
        ],
        'queue' => [
            'type' => 'text',
            'comment' => 'Queue name',
        ],
        'payload' => [
            'type' => 'longtext',
            'comment' => 'Serialized job data',
        ],
        'exception' => [
            'type' => 'longtext',
            'comment' => 'Exception message and stack trace',
        ],
        'failed_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Timestamp when job failed',
        ],
    ],
    'indexes' => [
        ['fields' => ['uuid']],
        ['fields' => ['queue']],
    ],
    'version' => '2.0.0',
];